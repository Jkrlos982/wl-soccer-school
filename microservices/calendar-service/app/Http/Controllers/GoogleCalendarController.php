<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Services\GoogleCalendarService;
use App\Services\CalendarSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleCalendarController extends Controller
{
    private $googleService;
    private $syncService;
    
    public function __construct(
        GoogleCalendarService $googleService,
        CalendarSyncService $syncService
    ) {
        $this->googleService = $googleService;
        $this->syncService = $syncService;
    }
    
    /**
     * Get Google OAuth authorization URL
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $calendarId = $request->input('calendar_id');
            
            if (!$calendarId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calendar ID is required'
                ], 400);
            }
            
            $calendar = Calendar::findOrFail($calendarId);
            
            // Check if user has permission to manage this calendar
            if (!$calendar->canUserAccess(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to calendar'
                ], 403);
            }
            
            $authUrl = $this->googleService->getAuthUrl($calendarId);
            
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to get Google auth URL: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate authorization URL'
            ], 500);
        }
    }
    
    /**
     * Handle Google OAuth callback
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $code = $request->input('code');
            $state = $request->input('state');
            $error = $request->input('error');
            
            if ($error) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization denied: ' . $error
                ], 400);
            }
            
            if (!$code || !$state) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing authorization code or state'
                ], 400);
            }
            
            $result = $this->googleService->handleCallback($code, $state);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Calendar connected successfully',
                'calendar' => $result['calendar']
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to handle Google callback: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process authorization'
            ], 500);
        }
    }
    
    /**
     * Sync calendar with Google Calendar
     */
    public function syncCalendar(Request $request, $calendarId): JsonResponse
    {
        try {
            $calendar = Calendar::findOrFail($calendarId);
            
            // Check if user has permission to manage this calendar
            if (!$calendar->canUserAccess(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to calendar'
                ], 403);
            }
            
            $force = $request->boolean('force', false);
            
            if ($force) {
                $result = $this->syncService->forceSync($calendar);
            } else {
                $result = $this->syncService->syncCalendar($calendar);
            }
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Calendar synchronized successfully',
                    'last_sync' => $calendar->fresh()->last_sync_at
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Synchronization failed',
                    'error' => $calendar->fresh()->sync_error
                ], 500);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to sync calendar: ' . $e->getMessage(), [
                'calendar_id' => $calendarId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to synchronize calendar'
            ], 500);
        }
    }
    
    /**
     * Disconnect calendar from Google Calendar
     */
    public function disconnectCalendar($calendarId): JsonResponse
    {
        try {
            $calendar = Calendar::findOrFail($calendarId);
            
            // Check if user has permission to manage this calendar
            if (!$calendar->canUserAccess(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to calendar'
                ], 403);
            }
            
            $result = $this->syncService->disconnectCalendar($calendar);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Calendar disconnected successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to disconnect calendar'
                ], 500);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to disconnect calendar: ' . $e->getMessage(), [
                'calendar_id' => $calendarId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect calendar'
            ], 500);
        }
    }
    
    /**
     * Get calendar sync status
     */
    public function getSyncStatus($calendarId): JsonResponse
    {
        try {
            $calendar = Calendar::findOrFail($calendarId);
            
            // Check if user has permission to view this calendar
            if (!$calendar->canUserAccess(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to calendar'
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'sync_status' => [
                    'is_connected' => !empty($calendar->external_calendar_id),
                    'sync_enabled' => $calendar->allow_external_sync,
                    'status' => $calendar->sync_status,
                    'last_sync' => $calendar->last_sync_at,
                    'sync_error' => $calendar->sync_error,
                    'external_calendar_id' => $calendar->external_calendar_id
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to get sync status: ' . $e->getMessage(), [
                'calendar_id' => $calendarId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync status'
            ], 500);
        }
    }
    
    /**
     * List available Google calendars for connected account
     */
    public function listGoogleCalendars($calendarId): JsonResponse
    {
        try {
            $calendar = Calendar::findOrFail($calendarId);
            
            // Check if user has permission to manage this calendar
            if (!$calendar->canUserAccess(Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to calendar'
                ], 403);
            }
            
            if (!$calendar->external_credentials) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calendar not connected to Google'
                ], 400);
            }
            
            $result = $this->googleService->listCalendars($calendar->external_credentials);
            
            return response()->json([
                'success' => $result['success'],
                'calendars' => $result['calendars'] ?? [],
                'error' => $result['error'] ?? null
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to list Google calendars: ' . $e->getMessage(), [
                'calendar_id' => $calendarId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to list Google calendars'
            ], 500);
        }
    }
}
