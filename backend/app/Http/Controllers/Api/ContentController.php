<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\Content;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
        $this->middleware(function ($request, $next) {
            try {
                authorize(fn($user) => $user->hasAnyRole(['admin', 'editor']));
                return $next($request);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        })->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Page $page)
    {
        $contents = $page->contents()
            ->with(['creator', 'files'])
            ->orderBy('order')
            ->get();

        return $this->successResponse($contents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Page $page)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|string|in:wysiwyg,markdown,html',
                'content' => 'required|string',
                'order' => 'integer',
                'settings' => 'nullable|array'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors()->first(), 422);
        }

        $content = new Content($validated);
        $content->page_id = $page->id;
        $content->created_by = Auth::id();
        $content->updated_by = Auth::id();
        $content->save();

        return $this->successResponse($content, 'Content created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page, Content $content)
    {
        if ($content->page_id !== $page->id) {
            return $this->notFoundResponse();
        }

        $content->load(['creator', 'updater', 'files']);
        return $this->successResponse($content);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Page $page, Content $content)
    {
        if ($content->page_id !== $page->id) {
            return $this->notFoundResponse();
        }

        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|string|in:wysiwyg,markdown,html',
                'content' => 'sometimes|required|string',
                'order' => 'integer',
                'settings' => 'nullable|array'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors()->first(), 422);
        }

        $content->fill($validated);
        $content->updated_by = Auth::id();
        $content->save();

        return $this->successResponse($content, 'Content updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page, Content $content)
    {
        if ($content->page_id !== $page->id) {
            return $this->notFoundResponse();
        }

        $content->delete();
        return $this->successResponse(null, 'Content deleted successfully');
    }

    /**
     * Reorder content blocks
     */
    public function reorder(Request $request, Page $page)
    {
        try {
            $validated = $request->validate([
                'order' => 'required|array',
                'order.*' => 'required|integer|distinct'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors()->first(), 422);
        }

        foreach ($validated['order'] as $index => $id) {
            Content::where('id', $id)
                ->where('page_id', $page->id)
                ->update(['order' => $index]);
        }

        return $this->successResponse(null, 'Content reordered successfully');
    }
}
