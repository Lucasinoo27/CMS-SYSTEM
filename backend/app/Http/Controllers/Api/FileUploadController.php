<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\FileUpload;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
        $this->middleware('role:admin,editor');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Content $content)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $file = $request->file('file');
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/' . date('Y/m'), $filename, 'public');

        $fileUpload = new FileUpload([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'created_by' => Auth::id()
        ]);

        $content->files()->save($fileUpload);

        return $this->successResponse($fileUpload, 'File uploaded successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Content $content, FileUpload $file)
    {
        if ($file->uploadable_type !== Content::class || $file->uploadable_id !== $content->id) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($file);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Content $content, FileUpload $file)
    {
        if ($file->uploadable_type !== Content::class || $file->uploadable_id !== $content->id) {
            return $this->notFoundResponse();
        }

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return $this->successResponse(null, 'File deleted successfully');
    }
}
