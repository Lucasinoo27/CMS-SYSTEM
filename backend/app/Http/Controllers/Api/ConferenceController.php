<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ConferenceController extends Controller
{
    /**
     * Display a listing of the conferences.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Cache conferences for 10 minutes to improve performance
        $conferences = Cache::remember('conferences.all', 600, function () {
            return Conference::orderBy('start_date', 'desc')->get();
        });
        
        return response()->json($conferences);
    }

    /**
     * Store a newly created conference in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Create conference data with generated slug
        $conferenceData = $validated;
        $conferenceData['slug'] = Str::slug($validated['name']);
        $conferenceData['status'] = $validated['status'] ?? 'draft';
        
        $conference = Conference::create($conferenceData);
        
        // Forget cache when creating a new conference
        Cache::forget('conferences.all');
        
        return response()->json($conference, 201);
    }

    /**
     * Display the specified conference.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Cache individual conference for 10 minutes
        $conference = Cache::remember("conferences.{$id}", 600, function () use ($id) {
            return Conference::findOrFail($id);
        });
        
        return response()->json($conference);
    }

    /**
     * Update the specified conference in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $conference = Conference::findOrFail($id);
        
        // Update data with slug if name changed
        $updateData = $validated;
        if (isset($validated['name'])) {
            $updateData['slug'] = Str::slug($validated['name']);
        }
        
        $conference->update($updateData);
        
        // Forget cache when updating a conference
        Cache::forget("conferences.{$id}");
        Cache::forget('conferences.all');
        
        return response()->json($conference);
    }

    /**
     * Remove the specified conference from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $conference = Conference::findOrFail($id);
        $conference->delete();
        
        // Forget cache when deleting a conference
        Cache::forget("conferences.{$id}");
        Cache::forget('conferences.all');
        
        return response()->json(null, 204);
    }
}
