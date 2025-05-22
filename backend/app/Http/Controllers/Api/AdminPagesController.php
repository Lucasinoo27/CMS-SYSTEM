<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\Page;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminPagesController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
        // Use authorize helper directly
        $this->middleware(function ($request, $next) {
            try {
                authorize(fn($user) => $user->isAdmin());
                return $next($request);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        });
    }

    /**
     * Get all pages with their conferences.
     * 
     * @return \Illuminate\Http\Response
     */
    public function getAllPages()
    {
        // Cache for 5 minutes to improve performance
        $data = Cache::remember('admin.pages.all', 300, function () {
            // Get all pages with their related conference using eager loading
            $pages = Page::with(['conference', 'creator'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'title' => $page->title,
                        'slug' => $page->slug,
                        'meta_description' => $page->meta_description,
                        'layout' => $page->layout,
                        'is_published' => $page->is_published,
                        'conference_id' => $page->conference_id,
                        'conference_name' => $page->conference->name,
                        'created_by' => $page->creator ? $page->creator->name : null,
                        'created_at' => $page->created_at,
                        'updated_at' => $page->updated_at,
                    ];
                });

            // Get all conferences
            $conferences = Conference::orderBy('name')->get(['id', 'name', 'slug']);

            return [
                'pages' => $pages,
                'conferences' => $conferences
            ];
        });

        return $this->successResponse($data);
    }

    /**
     * Get page count by conference.
     * 
     * @return \Illuminate\Http\Response
     */
    public function getPageCountsByConference()
    {
        // Cache for 5 minutes to improve performance
        $conferences = Cache::remember('admin.pages.counts', 300, function () {
            return Conference::withCount('pages')->get(['id', 'name', 'pages_count']);
        });
        
        return $this->successResponse($conferences);
    }
}
