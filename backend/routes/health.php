<?php

use Illuminate\Support\Facades\Route;

// Health check routes
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is online'], 200);
}); 