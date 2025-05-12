<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\FileUploadController;

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

// Health check route
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is online'], 200);
});

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);
});

// The following routes will be implemented in future phases

// Admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/stats', [App\Http\Controllers\Api\AdminController::class, 'getStats']);
    Route::get('/pages', [App\Http\Controllers\Api\AdminPagesController::class, 'getAllPages']);
    Route::get('/pages/counts', [App\Http\Controllers\Api\AdminPagesController::class, 'getPageCountsByConference']);
});

// Conference routes
Route::apiResource('conferences', App\Http\Controllers\Api\ConferenceController::class);

// User management routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
});

// Basic placeholder routes for testing
Route::get('/events', function() {
    return response()->json(['message' => 'Events endpoint working']);
});

Route::get('/papers', function() {
    return response()->json(['message' => 'Papers endpoint working']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Conference Pages
Route::prefix('conferences/{conference}')->group(function () {
    Route::apiResource('pages', PageController::class);
    
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
