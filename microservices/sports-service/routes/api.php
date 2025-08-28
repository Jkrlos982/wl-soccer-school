<?php

use Illuminate\Http\Request;
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

// Health check route
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Sports Service is running',
        'service' => 'sports-service',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString()
    ]);
});

// API Version 1 routes
Route::prefix('v1')->group(function () {
    
    // Public routes (no authentication required)
    Route::prefix('public')->group(function () {
        // Add public routes here if needed
    });
    
    // Protected routes (authentication required)
    Route::middleware(['jwt.auth'])->group(function () {
        
        // Sports routes will be added here
        // Example structure:
        // Route::apiResource('sports', SportController::class);
        // Route::apiResource('teams', TeamController::class);
        // Route::apiResource('players', PlayerController::class);
        // Route::apiResource('matches', MatchController::class);
        // Route::apiResource('tournaments', TournamentController::class);
        
        // User info route
        Route::get('/user', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'User information retrieved successfully',
                'data' => $request->auth_user
            ]);
        });
    });
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error_code' => 'ENDPOINT_NOT_FOUND'
    ], 404);
});