<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConferenceController extends Controller
{
    /**
     * Display a listing of the conferences.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $conferences = Conference::all();
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create conference data with generated slug
        $conferenceData = $request->all();
        $conferenceData['slug'] = Str::slug($request->name);
        $conferenceData['status'] = $request->status ?? 'draft';
        
        $conference = Conference::create($conferenceData);
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
        $conference = Conference::findOrFail($id);
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
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conference = Conference::findOrFail($id);
        
        // Update data with slug if name changed
        $updateData = $request->all();
        if ($request->has('name')) {
            $updateData['slug'] = Str::slug($request->name);
        }
        
        $conference->update($updateData);
        
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
        
        return response()->json(null, 204);
    }
}
