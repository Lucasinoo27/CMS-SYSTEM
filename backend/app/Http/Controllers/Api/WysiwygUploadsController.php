<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WysiwygUploadsController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        // All routes require authentication
        $this->middleware(['auth:sanctum']);
        $this->middleware(function ($request, $next) {
            try {
                authorize(fn($user) => $user->hasAnyRole(['admin', 'editor']));
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return $next($request);
        });
    }

    /**
     * Store a newly uploaded file from the WYSIWYG editor.
     */
    public function upload(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMessage = $e->errors()['file'][0] ?? 'Validation error';
            return $this->errorResponse($errorMessage, 422);
        }

        $file = $request->file('file');
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/wysiwyg/' . date('Y/m'), $filename, 'public');

        $fileUpload = new FileUpload([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'created_by' => Auth::id(),
            'uploadable_type' => 'App\\Models\\Content',
            'uploadable_id' => 0
        ]);

        $fileUpload->save();

        // Generate absolute URL for the file
        $fullUrl = url('/storage/' . $path);

        // Return data in the format expected by TinyMCE
        return response()->json([
            'location' => $fullUrl,
            'url' => $fullUrl,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $file->getMimeType()
        ]);
    }
} 