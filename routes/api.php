<?php

use Illuminate\Support\Facades\Route;

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

// Health check endpoint (no rate limiting needed)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'service' => env('APP_NAME', 'backend'),
        'version' => '1.0.0'
    ]);
});

Route::prefix('v1')->group(function () {
    // Rate-limited endpoints (1 request per minute per user)
    // Option 1: Using built-in throttle middleware
    Route::middleware(['throttle:game-operations'])->group(function () {

    });

    // Leaderboard with specific rate limiting (10 requests/minute) and caching
    Route::middleware(['throttle:leaderboard'])->group(function () {

    });


});
