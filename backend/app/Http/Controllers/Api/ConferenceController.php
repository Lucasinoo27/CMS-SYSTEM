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
     * Valid conference locations based on partner universities
     */
    private array $validLocations = [
        'Ljubljana, Slovenia',
        'Zagreb, Croatia',
        'Osijek, Croatia',
        'Vienna, Austria',
        'Padua, Italy',
        'Prague, Czech Republic',
        'Budapest, Hungary',
        'Nitra, Slovakia'
    ];

    /**
     * Display a listing of the conferences.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Cache conferences for 10 minutes to improve performance
        $conferences = Cache::remember('conferences.all', 600, function () {
            return Conference::with(['editors', 'pages' => function ($query) {
                $query->where('status', 'published')
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('start_date', 'desc')
            ->get();
        });
        
        return response()->json($conferences);
    }

    /**
     * Get valid conference locations.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLocations()
    {
        return response()->json($this->validLocations);
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
                'location' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (!in_array($value, $this->validLocations)) {
                        $fail('The selected location is not valid. Please choose from the partner university locations.');
                    }
                }],
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
        
        // Load relationships for the response
        $conference->load(['editors', 'pages' => function ($query) {
            $query->where('status', 'published')
                ->orderBy('created_at', 'desc');
        }]);
        
        // Forget cache when creating a new conference
        Cache::forget('conferences.all');
        Cache::forget('admin.pages.all');
        Cache::forget('admin.pages.counts');
        
        return response()->json($conference, 201);
    }

    /**
     * Display the specified conference.
     *
     * @param  string  $idOrSlug
     * @return \Illuminate\Http\Response
     */
    public function show($idOrSlug)
    {
        // Cache conference for 10 minutes
        $conference = Cache::remember("conferences.{$idOrSlug}", 600, function () use ($idOrSlug) {
            // Check if numeric ID or string slug
            $query = is_numeric($idOrSlug)
                ? Conference::where('id', $idOrSlug)
                : Conference::where('slug', $idOrSlug);
                
            return $query->with(['editors', 'pages' => function ($query) {
                $query->where('status', 'published')
                    ->orderBy('created_at', 'desc');
            }])->firstOrFail();
        });
        
        return response()->json($conference);
    }

    /**
     * Update the specified conference in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $idOrSlug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $idOrSlug)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'location' => ['sometimes', 'required', 'string', function ($attribute, $value, $fail) {
                    if (!in_array($value, $this->validLocations)) {
                        $fail('The selected location is not valid. Please choose from the partner university locations.');
                    }
                }],
                'status' => 'sometimes|required|string',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Find by ID or slug
        $conference = is_numeric($idOrSlug)
            ? Conference::findOrFail($idOrSlug)
            : Conference::where('slug', $idOrSlug)->firstOrFail();
        
        // Update data with slug if name changed
        $updateData = $validated;
        if (isset($validated['name'])) {
            $updateData['slug'] = Str::slug($validated['name']);
        }
        
        $conference->update($updateData);
        
        // Load relationships for the response
        $conference->load(['editors', 'pages' => function ($query) {
            $query->where('status', 'published')
                ->orderBy('created_at', 'desc');
        }]);
        
        // Forget cache
        Cache::forget("conferences.{$idOrSlug}");
        Cache::forget('conferences.all');
        Cache::forget('admin.pages.all');
        Cache::forget('admin.pages.counts');
        
        return response()->json($conference);
    }

    /**
     * Remove the specified conference from storage.
     *
     * @param  string  $idOrSlug
     * @return \Illuminate\Http\Response
     */
    public function destroy($idOrSlug)
    {
        // Find by ID or slug
        $conference = is_numeric($idOrSlug)
            ? Conference::findOrFail($idOrSlug)
            : Conference::where('slug', $idOrSlug)->firstOrFail();
            
        $conference->delete();
        
        // Forget cache
        Cache::forget("conferences.{$idOrSlug}");
        Cache::forget('conferences.all');
        Cache::forget('admin.pages.all');
        Cache::forget('admin.pages.counts');
        
        return response()->json(null, 204);
    }
}
