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
        // Categories for testing (temporary)
        Route::apiResource('categories', \App\Http\Controllers\Api\V1\CategoryController::class)
            ->names([
                'index' => 'api.v1.public.categories.index',
                'store' => 'api.v1.public.categories.store',
                'show' => 'api.v1.public.categories.show',
                'update' => 'api.v1.public.categories.update',
                'destroy' => 'api.v1.public.categories.destroy'
            ]);
        
        // Teams for testing (temporary)
        Route::apiResource('teams', \App\Http\Controllers\Api\V1\TeamController::class)
            ->names([
                'index' => 'api.v1.public.teams.index',
                'store' => 'api.v1.public.teams.store',
                'show' => 'api.v1.public.teams.show',
                'update' => 'api.v1.public.teams.update',
                'destroy' => 'api.v1.public.teams.destroy'
            ]);
    });
    
    // Protected routes (authentication required)
    Route::middleware(['jwt.auth'])->group(function () {
        
        // Sports routes
        Route::apiResource('categories', \App\Http\Controllers\Api\V1\CategoryController::class)
            ->names([
                'index' => 'api.v1.categories.index',
                'store' => 'api.v1.categories.store',
                'show' => 'api.v1.categories.show',
                'update' => 'api.v1.categories.update',
                'destroy' => 'api.v1.categories.destroy'
            ]);
        
        Route::apiResource('players', \App\Http\Controllers\Api\V1\PlayerController::class)
            ->names([
                'index' => 'api.v1.players.index',
                'store' => 'api.v1.players.store',
                'show' => 'api.v1.players.show',
                'update' => 'api.v1.players.update',
                'destroy' => 'api.v1.players.destroy'
            ]);
        
        // Additional player routes
        Route::get('categories/{categoryId}/players', [\App\Http\Controllers\Api\V1\PlayerController::class, 'byCategory'])
            ->name('api.v1.players.by-category');
        Route::get('players/{player}/statistics', [\App\Http\Controllers\Api\V1\PlayerController::class, 'statistics'])
            ->name('api.v1.players.statistics');
        Route::post('players/{player}/upload-photo', [\App\Http\Controllers\Api\V1\PlayerController::class, 'uploadPhoto'])
            ->name('api.v1.players.upload-photo');
        
        // Teams routes
        Route::apiResource('teams', \App\Http\Controllers\Api\V1\TeamController::class)
            ->names([
                'index' => 'api.v1.teams.index',
                'store' => 'api.v1.teams.store',
                'show' => 'api.v1.teams.show',
                'update' => 'api.v1.teams.update',
                'destroy' => 'api.v1.teams.destroy'
            ]);
        
        // Future sports routes:
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