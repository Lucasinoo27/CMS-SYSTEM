<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EditorConferenceController extends Controller
{
    /**
     * Get all conferences assigned to the authenticated editor.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyConferences()
    {
        $user = Auth::user();
        
        if (!$user->isEditor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $conferences = $user->conferences()
            ->with(['pages' => function ($query) {
                $query->select('id', 'conference_id', 'title', 'description', 'content');
            }])
            ->get();
        
        return response()->json($conferences);
    }

    /**
     * Update a page's content.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $pageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePage(Request $request, $pageId)
    {
        $user = Auth::user();
        
        if (!$user->isEditor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'content' => 'required|string'
        ]);
        
        // Get the page and check if the user has access to its conference
        $page = Page::findOrFail($pageId);
        $hasAccess = $user->conferences()
            ->where('conferences.id', $page->conference_id)
            ->exists();
            
        if (!$hasAccess) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $page->content = $request->content;
        $page->save();
        
        return response()->json([
            'message' => 'Page updated successfully',
            'page' => $page
        ]);
    }
} 