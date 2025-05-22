<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\Page;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
        // Use individual middleware instead of role middleware
        $this->middleware(function ($request, $next) {
            // Use new authorize helper function
            try {
                authorize(fn($user) => $user->isAdmin() || $user->isEditor());
                return $next($request);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        })->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Conference $conference)
    {
        $query = $conference->pages();
        
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('editor')) {
            $query->where('is_published', true);
        }

        $pages = $query->with(['contents', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->successResponse($pages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Conference $conference)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'meta_description' => 'nullable|string|max:255',
                'layout' => 'required|string|in:default,full-width,sidebar',
                'is_published' => 'boolean'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors()->first(), 422);
        }

        $page = new Page($validated);
        $page->slug = Str::slug($validated['title']);
        $page->conference_id = $conference->id;
        $page->created_by = Auth::id();
        $page->updated_by = Auth::id();
        $page->save();

        return $this->successResponse($page, 'Page created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Conference $conference, Page $page)
    {
        if (!$page->is_published) {
            try {
                authorize(fn($user) => $user->hasAnyRole(['admin', 'editor']));
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return $this->forbiddenResponse();
            }
        }

        $page->load(['contents', 'creator', 'updater']);
        return $this->successResponse($page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Conference $conference, Page $page)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'meta_description' => 'nullable|string|max:255',
                'layout' => 'sometimes|required|string|in:default,full-width,sidebar',
                'is_published' => 'boolean'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors()->first(), 422);
        }

        if (isset($validated['title'])) {
            $page->slug = Str::slug($validated['title']);
        }
        
        $page->fill($validated);
        $page->updated_by = Auth::id();
        $page->save();

        return $this->successResponse($page, 'Page updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conference $conference, Page $page)
    {
        $page->delete();
        return $this->successResponse(null, 'Page deleted successfully');
    }
}
