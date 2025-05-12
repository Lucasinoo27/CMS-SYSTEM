<?php

use App\Http\Controllers\Api\AuthController;
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

// Basic placeholder routes for testing
Route::get('/conferences', function() {
    return response()->json(['message' => 'Conferences endpoint working']);
});

Route::get('/events', function() {
    return response()->json(['message' => 'Events endpoint working']);
});

Route::get('/papers', function() {
    return response()->json(['message' => 'Papers endpoint working']);
});
