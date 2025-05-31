<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserConferenceController extends Controller
{
    /**
     * Get all conferences assigned to a user.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserConferences($userId)
    {
        $user = User::findOrFail($userId);
        $conferences = $user->conferences;
        
        return response()->json($conferences);
    }

    /**
     * Update the conferences assigned to a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserConferences(Request $request, $userId)
    {
        $request->validate([
            'conference_ids' => 'required|array',
            'conference_ids.*' => 'exists:conferences,id'
        ]);

        $user = User::findOrFail($userId);
        
        DB::transaction(function () use ($user, $request) {
            // Sync the conferences (this will handle both adding and removing)
            $user->conferences()->sync($request->conference_ids);
        });

        // Clear caches that might contain conference data
        Cache::forget('conferences.all');
        Cache::forget('admin.pages.all');
        Cache::forget('admin.pages.counts');

        return response()->json([
            'message' => 'Conference assignments updated successfully',
            'conferences' => $user->conferences
        ]);
    }
} 