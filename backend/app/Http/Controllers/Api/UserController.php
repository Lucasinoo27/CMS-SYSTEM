<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('roles')->get();
        
        // Transform user data to include role information
        $users = $users->map(function ($user) {
            $role = $user->roles->isNotEmpty() ? $user->roles->first()->name : 'user';
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });
        
        return response()->json($users);
    }

    /**
     * Store a newly created user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => 'required|string|in:admin,editor',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role
        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->roles()->attach($role);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        $role = $user->roles->isNotEmpty() ? $user->roles->first()->name : 'user';
        
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        return response()->json($userData);
    }

    /**
     * Update the specified user.
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
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
                'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
                'role' => 'sometimes|required|string|in:admin,editor',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = User::findOrFail($id);
        
        // Update user data
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();
        
        // Update role if provided
        if (isset($validated['role'])) {
            // Remove all current roles
            $user->roles()->detach();
            
            // Attach new role
            $role = Role::where('name', $validated['role'])->first();
            if ($role) {
                $user->roles()->attach($role);
            }
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Remove user's roles
        $user->roles()->detach();
        
        // Delete user
        $user->delete();
        
        return response()->json(null, 204);
    }

    /**
     * Get all conferences for a user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getConferences($id)
    {
        try {
            $user = User::findOrFail($id);
            
            if (!$user->hasRole('editor')) {
                return response()->json(['message' => 'Only editors can have conferences'], 403);
            }
            
            $conferences = $user->conferences()
                ->select('conferences.id', 'conferences.name', 'conferences.slug')
                ->get();
            
            \Log::info('Fetched conferences for user', [
                'user_id' => $id,
                'conferences_count' => $conferences->count(),
                'conferences' => $conferences->toArray()
            ]);
                
            return response()->json($conferences);
        } catch (\Exception $e) {
            \Log::error('Error fetching user conferences: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch conferences'], 500);
        }
    }

    public function getMyConferences()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                \Log::error('No authenticated user found in getMyConferences');
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            
            if (!$user->hasRole('editor')) {
                \Log::error('User ' . $user->id . ' is not an editor');
                return response()->json(['message' => 'Only editors can have conferences'], 403);
            }
            
            \Log::info('Fetching conferences for user ' . $user->id);
            
            $conferences = $user->conferences()
                ->with(['pages' => function($query) {
                    $query->select('id', 'conference_id', 'title', 'slug', 'status', 'updated_at')
                        ->orderBy('updated_at', 'desc');
                }])
                ->select('conferences.id', 'conferences.name', 'conferences.slug')
                ->get();
            
            \Log::info('Found ' . $conferences->count() . ' conferences for user ' . $user->id);
                
            return response()->json($conferences);
        } catch (\Exception $e) {
            \Log::error('Error in getMyConferences: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to fetch conferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign conferences to a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function assignConferences(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'conference_ids' => 'array',
                'conference_ids.*' => 'integer|exists:conferences,id'
            ]);

            $user = User::findOrFail($id);
            
            // Check if user is an editor
            $isEditor = $user->roles()->where('name', 'editor')->exists();
            if (!$isEditor) {
                \Log::warning('Attempt to assign conferences to non-editor user', [
                    'user_id' => $id,
                    'email' => $user->email
                ]);
                return response()->json([
                    'message' => 'Only editors can be assigned to conferences',
                    'user_role' => $user->roles()->pluck('name')->first()
                ], 403);
            }

            // Begin transaction
            DB::beginTransaction();
            
            try {
                // Log the conference assignment attempt
                \Log::info('Attempting to assign conferences', [
                    'user_id' => $id,
                    'conference_ids' => $validated['conference_ids'] ?? []
                ]);

                // Sync the conferences (this will remove any existing assignments and add new ones)
                $user->conferences()->sync($validated['conference_ids'] ?? []);
                
                // Get the updated conferences with all necessary fields
                $conferences = $user->conferences()
                    ->select('conferences.id', 'conferences.name', 'conferences.slug')
                    ->get();
                
                DB::commit();
                
                \Log::info('Successfully assigned conferences', [
                    'user_id' => $id,
                    'assigned_conferences' => $conferences->pluck('name'),
                    'conference_count' => $conferences->count()
                ]);

                return response()->json([
                    'message' => 'Conferences assigned successfully',
                    'conferences' => $conferences,
                    'conference_count' => $conferences->count()
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Database error while assigning conferences', [
                    'user_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation error in conference assignment', [
                'errors' => $e->errors(),
                'user_id' => $id
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error in assignConferences', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to assign conferences: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove conference assignments from a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function removeConferences(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'conference_ids' => 'required|array',
                'conference_ids.*' => 'exists:conferences,id'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = User::findOrFail($id);
        
        // Detach the specified conferences
        $user->conferences()->detach($validated['conference_ids']);
        
        return response()->json([
            'message' => 'Conferences removed successfully',
            'conferences' => $user->conferences()->get(['id', 'name', 'slug'])
        ]);
    }
}
