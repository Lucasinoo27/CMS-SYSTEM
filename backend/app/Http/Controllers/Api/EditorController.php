<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conference;
use App\Models\Page;
use App\Models\Content;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EditorController extends Controller
{
    /**
     * Get editor dashboard statistics
     */
    public function getStats()
    {
        $user = Auth::user();
        
        // Ensure the user is an editor
        if (!$user->isEditor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Get conferences assigned to this editor
        $assignedConferences = Conference::whereHas('editors', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
        
        // Get pages managed by this editor
        $managedPages = Page::whereHas('conference.editors', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
        
        // Get content blocks in pages managed by this editor
        $contentBlocks = Content::whereHas('page.conference.editors', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
        
        // Get files uploaded by this editor
        $uploadedFiles = FileUpload::where('created_by', $user->id)->count();
        
        return response()->json([
            'assignedConferences' => $assignedConferences,
            'managedPages' => $managedPages,
            'contentBlocks' => $contentBlocks,
            'uploadedFiles' => $uploadedFiles
        ]);
    }
    
    /**
     * Get editor recent activities
     */
    public function getActivities()
    {
        $user = Auth::user();
        
        // Ensure the user is an editor
        if (!$user->isEditor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Create some sample activities for now
        // In a real implementation, you would fetch from an activities table
        $activities = [
            [
                'id' => 1,
                'type' => 'page',
                'message' => 'You created a new page "Welcome Page"',
                'created_at' => Carbon::now()->subHours(2)->toDateTimeString()
            ],
            [
                'id' => 2,
                'type' => 'content',
                'message' => 'You updated content on "Schedule Page"',
                'created_at' => Carbon::now()->subHours(5)->toDateTimeString()
            ],
            [
                'id' => 3,
                'type' => 'file',
                'message' => 'You uploaded "conference-schedule.pdf"',
                'created_at' => Carbon::now()->subDays(1)->toDateTimeString()
            ],
            [
                'id' => 4,
                'type' => 'conference',
                'message' => 'You were assigned to "Animal Science Days 2024"',
                'created_at' => Carbon::now()->subDays(2)->toDateTimeString()
            ]
        ];
        
        return response()->json($activities);
    }

    /**
     * Get pages assigned to the editor
     */
    public function getAssignedPages()
    {
        $user = Auth::user();
        
        // Ensure the user is an editor
        if (!$user->isEditor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Get pages from conferences assigned to this editor
        $pages = Page::whereHas('conference.editors', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['conference', 'creator'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'conference_name' => $page->conference->name,
                'status' => $page->is_published ? 'published' : 'draft',
                'updated_at' => $page->updated_at
            ];
        });
        
        return response()->json($pages);
    }
} 