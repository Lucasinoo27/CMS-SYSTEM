<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\Content;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
        $this->middleware('role:admin,editor')->except(['index', 'show']);
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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:wysiwyg,markdown,html',
            'content' => 'required|string',
            'order' => 'integer',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $content = new Content($request->all());
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

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:wysiwyg,markdown,html',
            'content' => 'sometimes|required|string',
            'order' => 'integer',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $content->fill($request->all());
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
        $validator = Validator::make($request->all(), [
            'order' => 'required|array',
            'order.*' => 'required|integer|distinct'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        foreach ($request->order as $index => $id) {
            Content::where('id', $id)
                ->where('page_id', $page->id)
                ->update(['order' => $index]);
        }

        return $this->successResponse(null, 'Content reordered successfully');
    }
}
