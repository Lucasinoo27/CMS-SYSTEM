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
        $this->middleware(['auth:sanctum'], ['except' => ['publicUpload']]);
        $this->middleware(function ($request, $next) {
            if ($request->route()->getName() !== 'wysiwyg.public.upload') {
                try {
                    authorize(fn($user) => $user->hasAnyRole(['admin', 'editor']));
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
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
            return $this->errorResponse($e->errors()->first(), 422);
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
            'uploadable_type' => null,
            'uploadable_id' => null
        ]);

        $fileUpload->save();

        // Return data in the format expected by TinyMCE
        return response()->json([
            'location' => Storage::disk('public')->url($path),
            'url' => Storage::disk('public')->url($path),
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $file->getMimeType()
        ]);
    }
    
    /**
     * Store a newly uploaded file from the WYSIWYG editor without authentication.
     * This is for public-facing pages.
     */
    public function publicUpload(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:5120', // 5MB max for public uploads
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()->first()], 422);
        }

        $file = $request->file('file');
        
        // Additional security checks for public uploads
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json(['error' => 'File type not allowed'], 422);
        }
        
        $filename = Str::random(40) . '.' . $extension;
        $path = $file->storeAs('uploads/public/' . date('Y/m'), $filename, 'public');

        $fileUpload = new FileUpload([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'created_by' => null, // No authenticated user
            'uploadable_type' => null,
            'uploadable_id' => null
        ]);

        $fileUpload->save();

        // Return data in the format expected by TinyMCE
        return response()->json([
            'location' => Storage::disk('public')->url($path),
            'url' => Storage::disk('public')->url($path),
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $file->getMimeType()
        ]);
    }
} 