<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\FileUpload;
use App\Models\Content;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        $this->middleware(['auth:sanctum'])->except(['getPageFiles', 'download', 'removeFromPage']);
        $this->middleware(function ($request, $next) {
            // Skip authorization for public methods
            if (in_array($request->route()->getActionMethod(), ['getPageFiles', 'download', 'removeFromPage'])) {
                return $next($request);
            }
            
            // Check for admin or editor role
            try {
                authorize(fn($user) => $user->hasAnyRole(['admin', 'editor']));
                return $next($request);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        });
    }

    /**
     * Display a listing of all files.
     */
    public function index()
    {
        $files = FileUpload::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return $this->successResponse($files);
    }

    /**
     * Upload a file and handle validation
     * 
     * @param Request $request
     * @param string $subDirectory
     * @return array|null
     */
    private function processFileUpload(Request $request, $subDirectory = 'uploads')
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
            ]);
            
            $file = $request->file('file');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($subDirectory . '/' . date('Y/m'), $filename, 'public');
            
            return [
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'created_by' => Auth::id()
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = is_array($errors) && count($errors) > 0 ? reset($errors)[0] : 'Validation failed';
            throw new \Exception($firstError, 422);
        }
    }

    /**
     * Store a file for a content
     */
    public function store(Request $request, Content $content)
    {
        try {
            $fileData = $this->processFileUpload($request);
            $fileUpload = new FileUpload($fileData);
            $content->files()->save($fileUpload);
            
            return $this->successResponse($fileUpload, 'File uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Store a file and assign it to a page.
     */
    public function storeForPage(Request $request, $pageId)
    {
        try {
            $page = Page::findOrFail($pageId);
            $fileData = $this->processFileUpload($request, 'uploads/pages');
            
            $fileUpload = new FileUpload($fileData);
            $page->files()->save($fileUpload);
            
            return $this->successResponse($fileUpload, 'File uploaded successfully', 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error uploading file: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Assign an existing file to a page.
     */
    public function assignToPage(Request $request, $pageId)
    {
        try {
            $validated = $request->validate(['file_id' => 'required|exists:file_uploads,id']);
            $page = Page::findOrFail($pageId);
            $file = FileUpload::findOrFail($validated['file_id']);
            
            // Check permission
            authorize(fn($user) => $user->id === $file->created_by || $user->hasAnyRole(['admin']));
            
            // Update relationship
            $file->uploadable_type = 'App\\Models\\Page';
            $file->uploadable_id = $page->id;
            $file->save();

            return $this->successResponse($file, 'File assigned to page successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors()['file_id'][0] ?? 'Invalid file ID', 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('You do not have permission to assign this file', 403);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove a file association from a page.
     *
     * @param int $conference The conference ID
     * @param int $page The page ID
     * @param int $file The file ID
     * @return \Illuminate\Http\Response
     */
    public function removeFromPage($conference, $page, $file)
    {
        try {
            // Log all parameters for debugging
            \Illuminate\Support\Facades\Log::info("RemoveFromPage called with: conference={$conference}, page={$page}, file={$file}");
            
            // Try to find the file
            $fileModel = FileUpload::find($file);
            if (!$fileModel) {
                return $this->errorResponse('File not found', 404);
            }
            
            // Change ownership regardless of current association
            $fileModel->uploadable_type = 'App\\Models\\User';
            $fileModel->uploadable_id = Auth::id() ?: 1; // Fallback to admin ID if not authenticated
            $fileModel->save();

            \Illuminate\Support\Facades\Log::info("File {$file} successfully unlinked from page {$page}");
            return $this->successResponse(null, 'File removed from page successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to remove file: ' . $e->getMessage());
            return $this->errorResponse('Failed to remove file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all files assigned to a page.
     */
    public function getPageFiles($pageId)
    {
        try {
            $page = Page::findOrFail($pageId);
            $files = $page->files()->with('creator')->get();
            return $this->successResponse($files);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get page files: ' . $e->getMessage(), 404);
        }
    }

    /**
     * Get a specific file for a content
     */
    public function show(Content $content, FileUpload $file)
    {
        if ($file->uploadable_type !== Content::class || $file->uploadable_id !== $content->id) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($file);
    }

    /**
     * Delete a file associated with content
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
    
    /**
     * Upload a general file (not associated with content)
     */
    public function uploadGeneral(Request $request)
    {
        try {
            $fileData = $this->processFileUpload($request);
            
            $fileUpload = new FileUpload(array_merge($fileData, [
                'uploadable_type' => 'App\\Models\\User',
                'uploadable_id' => Auth::id()
            ]));
            
            $fileUpload->save();
            
            return $this->successResponse($fileUpload, 'File uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Delete a file directly
     */
    public function destroyGeneral(FileUpload $file)
    {
        try {
            authorize(fn($user) => $user->id === $file->created_by || $user->hasAnyRole(['admin']));
            
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();

            return $this->successResponse(null, 'File deleted successfully');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('You do not have permission to delete this file', 403);
        }
    }

    /**
     * Download a file
     */
    public function download(FileUpload $file)
    {
        try {
            $filePath = storage_path('app/public/' . $file->path);
            
            if (!file_exists($filePath)) {
                $publicPath = public_path('storage/' . $file->path);
                
                if (!file_exists($publicPath)) {
                    return $this->errorResponse('File not found on server', 404);
                }
                
                $filePath = $publicPath;
            }
            
            return response()->download($filePath, $file->original_filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error downloading file: ' . $e->getMessage());
            return $this->errorResponse('Error downloading file: ' . $e->getMessage(), 500);
        }
    }
}
