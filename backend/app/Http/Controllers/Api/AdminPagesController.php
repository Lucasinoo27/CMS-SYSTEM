<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\Page;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPagesController extends Controller
{
    use HasApiResponses;

    // Middleware is already applied at the route level

    /**
     * Get all pages across all conferences.
     * 
     * @return \Illuminate\Http\Response
     */
    public function getAllPages()
    {
        // Get all pages with their related conference
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

        return $this->successResponse([
            'pages' => $pages,
            'conferences' => $conferences
        ]);
    }

    /**
     * Get page count by conference.
     * 
     * @return \Illuminate\Http\Response
     */
    public function getPageCountsByConference()
    {
        $conferences = Conference::withCount('pages')->get(['id', 'name', 'pages_count']);
        
        return $this->successResponse($conferences);
    }
}
