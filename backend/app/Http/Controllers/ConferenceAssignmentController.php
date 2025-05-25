<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConferenceAssignmentController extends Controller
{
    /**
     * Get all conferences for assignment
     */
    public function getConferences()
    {
        $conferences = Conference::all();
        return response()->json($conferences);
    }

    /**
     * Get assigned conferences for a user
     */
    public function getUserConferences($userId)
    {
        $user = User::findOrFail($userId);
        $conferences = $user->conferences;
        return response()->json($conferences);
    }

    /**
     * Assign conferences to a user
     */
    public function assignConferences(Request $request, $userId)
    {
        $request->validate([
            'conference_ids' => 'required|array',
            'conference_ids.*' => 'exists:conferences,id'
        ]);

        $user = User::findOrFail($userId);
        
        DB::transaction(function () use ($user, $request) {
            // Remove existing assignments
            $user->conferences()->detach();
            
            // Add new assignments
            $user->conferences()->attach($request->conference_ids);
        });

        return response()->json([
            'message' => 'Conferences assigned successfully',
            'conferences' => $user->conferences
        ]);
    }
} 