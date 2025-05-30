<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\Page;
use App\Models\Conference;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
            $query->where('status', 'published');
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
                'status' => 'required|string|in:draft,published',
                'blocks' => 'array',
                'blocks.*.type' => 'required|string|in:text,image,video,file',
                'blocks.*.content' => 'nullable',
                'blocks.*.alt' => 'nullable|string',
                'blocks.*.embed' => 'nullable|string',
                'blocks.*.fileName' => 'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->first()[0] ?? 'Validation error';
            return $this->errorResponse($firstError, 422);
            
        }

        // Extract blocks from the validated data
        $blocks = $validated['blocks'] ?? [];
        unset($validated['blocks']);

        // Create the page with proper data
        $page = Page::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'meta_description' => $validated['meta_description'] ?? null,
            'layout' => $validated['layout'],
            'status' => $validated['status'],
            'conference_id' => $conference->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        // Save the blocks as Content records
        $this->saveContentBlocks($page, $blocks);

        Cache::forget('admin.pages.all');
        return $this->successResponse($page, 'Page created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Conference $conference, Page $page)
    {
        // Check if the page belongs to the specified conference
        if ($page->conference_id !== $conference->id) {
            return response()->json(['success' => false, 'message' => 'Page not found in this conference'], 404);
        }

        // Load the page with its contents
        $page->load(['contents', 'creator', 'updater']);
        
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $page
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Conference $conference, Page $page)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'layout' => 'sometimes|required|string|in:default,full-width,sidebar',
                'meta_description' => 'nullable|string|max:255',
                'status' => 'sometimes|required|string|in:draft,published',
                'blocks' => 'array',
                'blocks.*.type' => 'required|string|in:text,image,video,file',
                'blocks.*.content' => 'nullable',
                'blocks.*.alt' => 'nullable|string',
                'blocks.*.embed' => 'nullable|string',
                'blocks.*.fileName' => 'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = $e->errors();
            $firstKey = array_key_first($firstError);
            $errorMessage = $firstError[$firstKey][0] ?? 'Validation error';
            return $this->errorResponse($errorMessage, 422);
        }

        // Extract blocks from the validated data
        $blocks = $validated['blocks'] ?? [];
        unset($validated['blocks']);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        $page->fill($validated);
        $page->updated_by = Auth::id();
        $page->save();

        // Update content blocks if present in request
        if (isset($request->blocks)) {
            $page->contents()->delete();
            $this->saveContentBlocks($page, $blocks);
        }
        
        return $this->successResponse($page, 'Page updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conference $conference, Page $page)
    {
        $page->delete();
        Cache::forget('admin.pages.all');
        return $this->successResponse(null, 'Page deleted successfully');
    }

    /**
     * Helper method to save content blocks for a page
     */
    private function saveContentBlocks(Page $page, array $blocks): void
    {
        if (empty($blocks)) {
            return;
        }
            
            
            // Create new content blocks
        
            // Create new content blocks
        foreach ($blocks as $index => $block) {
            $page->contents()->save(new Content([
                'type' => $block['type'],
                'content' => $block['content'] ?? '',
                'title' => $block['alt'] ?? '',
                'order' => $index,
                'settings' => [
                    'alt' => $block['alt'] ?? '',
                    'embed' => $block['embed'] ?? '',
                    'fileName' => $block['fileName'] ?? ''
                ],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]));
        }
    }
}
