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
        // Only protect non-public routes
        $this->middleware(['auth:sanctum'])->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Conference $conference)
    {
        $query = $conference->pages();
        
        // For anonymous users, only show published pages
        if (!Auth::check()) {
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
        // For anonymous users, only show published pages
        if (!Auth::check() && !$page->is_published) {
            return $this->forbiddenResponse();
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
