<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\WysiwygUploadsController;
use App\Http\Controllers\Api\ConferenceController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminPagesController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EditorController;
use App\Http\Controllers\Api\AdminStatsController;
use App\Http\Controllers\Api\ConferencePageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check route is now in routes/health.php

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);
});

// Admin routes - Now using authorize helper in controllers directly
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/pages', [AdminPagesController::class, 'getAllPages']);
    Route::get('/pages/counts', [AdminPagesController::class, 'getPageCountsByConference']);
});

// Editor routes
Route::prefix('editor')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/stats', [EditorController::class, 'getStats']);
    Route::get('/activities', [EditorController::class, 'getActivities']);
    Route::get('/pages', [EditorController::class, 'getAssignedPages']);
});

// Conference routes
Route::apiResource('conferences', ConferenceController::class);
Route::get('conference-locations', [ConferenceController::class, 'getLocations']);

// User management routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('users/me/conferences', [UserController::class, 'getMyConferences']);
    Route::get('users/{id}/conferences', [UserController::class, 'getConferences']);
    Route::post('users/{id}/conferences', [UserController::class, 'assignConferences']);
    Route::delete('users/{id}/conferences', [UserController::class, 'removeConferences']);
});

// WYSIWYG Editor uploads
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/uploads', [WysiwygUploadsController::class, 'upload']);
    Route::post('/files', [FileUploadController::class, 'uploadGeneral']);
    Route::get('/files', [FileUploadController::class, 'index']);
    Route::delete('/files/{file}', [FileUploadController::class, 'destroyGeneral']);
});

// Public file routes
Route::get('/files/{file}/download', [FileUploadController::class, 'download']);

// Basic placeholder routes for testing
Route::get('/events', fn() => response()->json(['message' => 'Events endpoint working']));
Route::get('/papers', fn() => response()->json(['message' => 'Papers endpoint working']));
Route::middleware('auth:sanctum')->get('/user', fn(Request $request) => $request->user());

// Conference Pages (public routes for viewing)
Route::prefix('conferences/{conference}')->group(function () {
    Route::get('pages', [PageController::class, 'index']);
    Route::get('pages/{page}', [PageController::class, 'show']);
    Route::get('pages/{page}/files', [FileUploadController::class, 'getPageFiles'])->where('page', '[0-9]+');
});

// Conference Pages (protected routes for editing)
Route::prefix('conferences/{conference}')->middleware(['auth:sanctum'])->group(function () {
    Route::post('pages', [PageController::class, 'store']);
    Route::put('pages/{page}', [PageController::class, 'update']);
    Route::delete('pages/{page}', [PageController::class, 'destroy']);
    
    // Page Files
    Route::prefix('pages/{page}')->group(function () {
        Route::post('files', [FileUploadController::class, 'storeForPage'])->where('page', '[0-9]+');
        Route::post('files/assign', [FileUploadController::class, 'assignToPage'])->where('page', '[0-9]+');
        Route::delete('files/{file}', [FileUploadController::class, 'removeFromPage']);
    });
    
    // Page Contents
    Route::prefix('pages/{page}')->group(function () {
        Route::apiResource('contents', ContentController::class);
        Route::post('contents/reorder', [ContentController::class, 'reorder']);
        
        // Content Files
        Route::prefix('contents/{content}')->group(function () {
            Route::post('files', [FileUploadController::class, 'store']);
            Route::get('files/{file}', [FileUploadController::class, 'show']);
            Route::delete('files/{file}', [FileUploadController::class, 'destroy']);
        });
    });
});

// Health check route
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is online'], 200);
});
