<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use Illuminate\Http\Request;

class PublicConferenceController extends Controller
{
    /**
     * Get conference details and content by slug.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConferenceBySlug($slug)
    {
        $conference = Conference::where('slug', $slug)
            ->with(['pages' => function ($query) {
                $query->select('id', 'conference_id', 'title', 'content')
                    ->where('status', 'published')
                    ->orderBy('order', 'asc');
            }])
            ->first();
            
        if (!$conference) {
            return response()->json(['message' => 'Conference not found'], 404);
        }
        
        return response()->json($conference);
    }
} 