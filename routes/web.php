<?php

use Illuminate\Support\Facades\Route;

// Health check route for frontend
Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Backend is running',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
