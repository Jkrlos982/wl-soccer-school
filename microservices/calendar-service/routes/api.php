<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\ReminderController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Google Calendar Integration Routes
|--------------------------------------------------------------------------
|
| Routes for Google Calendar OAuth and synchronization
|
*/

// Google Calendar OAuth routes
Route::prefix('google-calendar')->group(function () {
    // Get OAuth authorization URL
    Route::post('/auth/url', [GoogleCalendarController::class, 'getAuthUrl'])
        ->name('google-calendar.auth.url');
    
    // Handle OAuth callback
    Route::get('/auth/callback', [GoogleCalendarController::class, 'handleCallback'])
        ->name('google-calendar.auth.callback');
    
    // Calendar-specific routes
    Route::prefix('calendars/{calendarId}')->group(function () {
        // Sync calendar with Google Calendar
        Route::post('/sync', [GoogleCalendarController::class, 'syncCalendar'])
            ->name('google-calendar.sync');
        
        // Get sync status
        Route::get('/sync/status', [GoogleCalendarController::class, 'getSyncStatus'])
            ->name('google-calendar.sync.status');
        
        // Disconnect calendar from Google Calendar
        Route::delete('/disconnect', [GoogleCalendarController::class, 'disconnectCalendar'])
            ->name('google-calendar.disconnect');
        
        // List available Google calendars
        Route::get('/google-calendars', [GoogleCalendarController::class, 'listGoogleCalendars'])
            ->name('google-calendar.list');
    });
});

/*
|--------------------------------------------------------------------------
| Reminder System Routes
|--------------------------------------------------------------------------
|
| Routes for automatic reminder system management
|
*/

Route::prefix('reminders')->group(function () {
    // Get reminder statistics
    Route::get('/stats', [ReminderController::class, 'getStats'])
        ->name('reminders.stats');
    
    // Send immediate reminder
    Route::post('/send-immediate', [ReminderController::class, 'sendImmediate'])
        ->name('reminders.send-immediate');
    
    // Process reminders manually
    Route::post('/process', [ReminderController::class, 'processReminders'])
        ->name('reminders.process');
    
    // Get rate limit status
    Route::get('/rate-limit-status', [ReminderController::class, 'getRateLimitStatus'])
        ->name('reminders.rate-limit-status');
    
    // Get system health status
    Route::get('/health', [ReminderController::class, 'getHealthStatus'])
        ->name('reminders.health');
    
    // Get reminder configuration
    Route::get('/config', [ReminderController::class, 'getConfig'])
        ->name('reminders.config');
    
    // Admin routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Clear rate limit for a user
        Route::delete('/rate-limit/{userId}', [ReminderController::class, 'clearRateLimit'])
            ->name('reminders.clear-rate-limit');
    });
});

/*
|--------------------------------------------------------------------------
| Calendar Management Routes
|--------------------------------------------------------------------------
|
| Basic calendar CRUD operations (to be implemented)
|
*/

// These routes would be implemented in future tasks
// Route::apiResource('calendars', CalendarController::class);
// Route::apiResource('events', EventController::class);
// Route::apiResource('attendees', AttendeeController::class);
// Route::apiResource('resources', ResourceController::class);