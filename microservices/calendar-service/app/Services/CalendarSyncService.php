<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\Event;
use App\Events\CalendarSyncCompleted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class CalendarSyncService
{
    private $googleService;
    
    public function __construct(GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
    }
    
    public function syncCalendar(Calendar $calendar)
    {
        if (!$calendar->allow_external_sync || !$calendar->external_calendar_id) {
            return false;
        }
        
        $stats = [
            'events_created' => 0,
            'events_updated' => 0,
            'events_deleted' => 0,
            'errors' => 0
        ];
        
        try {
            // Set access token
            $token = $this->googleService->setAccessToken($calendar->external_credentials);
            
            if (isset($token['access_token'])) {
                // Update stored credentials if refreshed
                $calendar->update(['external_credentials' => $token]);
            }
            
            // Sync events from Google to local
            $fromExternalStats = $this->syncFromExternal($calendar);
            $stats['events_created'] += $fromExternalStats['created'];
            $stats['events_updated'] += $fromExternalStats['updated'];
            
            // Sync local events to Google
            $toExternalStats = $this->syncToExternal($calendar);
            $stats['events_created'] += $toExternalStats['created'];
            $stats['events_updated'] += $toExternalStats['updated'];
            
            $calendar->update([
                'last_sync_at' => now(),
                'sync_status' => 'active',
                'sync_error' => null
            ]);
            
            // Fire sync completed event
            event(new CalendarSyncCompleted($calendar, $stats, true));
            
            return true;
        } catch (Exception $e) {
            Log::error('Calendar sync failed: ' . $e->getMessage(), [
                'calendar_id' => $calendar->id
            ]);
            
            $calendar->update([
                'sync_status' => 'error',
                'sync_error' => $e->getMessage()
            ]);
            
            // Fire sync failed event
            event(new CalendarSyncCompleted($calendar, $stats, false, $e->getMessage()));
            
            return false;
        }
    }
    
    private function syncFromExternal(Calendar $calendar)
    {
        $result = $this->googleService->syncEvents($calendar->external_calendar_id);
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        
        $stats = ['created' => 0, 'updated' => 0];
        
        foreach ($result['events'] as $googleEvent) {
            $eventStats = $this->processExternalEvent($calendar, $googleEvent);
            $stats['created'] += $eventStats['created'];
            $stats['updated'] += $eventStats['updated'];
        }
        
        return $stats;
    }
    
    private function syncToExternal(Calendar $calendar)
    {
        $stats = ['created' => 0, 'updated' => 0];
        
        // Sync local events that haven't been synced
        $localEvents = $calendar->events()
            ->whereNull('external_event_id')
            ->where('updated_at', '>', $calendar->last_sync_at ?? now()->subDays(7))
            ->get();
            
        foreach ($localEvents as $event) {
            $result = $this->googleService->createEvent($calendar->external_calendar_id, $event);
            
            if ($result['success']) {
                $event->update([
                    'external_event_id' => $result['external_id'],
                    'last_sync_at' => now()
                ]);
                $stats['created']++;
            }
        }
        
        // Update modified local events
        $modifiedEvents = $calendar->events()
            ->whereNotNull('external_event_id')
            ->where('updated_at', '>', $calendar->last_sync_at ?? now()->subDays(7))
            ->get();
            
        foreach ($modifiedEvents as $event) {
            $result = $this->googleService->updateEvent(
                $calendar->external_calendar_id,
                $event->external_event_id,
                $event
            );
            
            if ($result['success']) {
                $event->update(['last_sync_at' => now()]);
                $stats['updated']++;
            }
        }
        
        return $stats;
    }
    
    private function processExternalEvent(Calendar $calendar, $googleEvent)
    {
        // Check if event already exists
        $existingEvent = Event::where('external_event_id', $googleEvent->getId())->first();
        
        $eventData = [
            'calendar_id' => $calendar->id,
            'school_id' => $calendar->school_id,
            'title' => $googleEvent->getSummary() ?? 'Sin título',
            'description' => $googleEvent->getDescription(),
            'location' => $googleEvent->getLocation(),
            'start_datetime' => $this->parseGoogleDateTime($googleEvent->getStart()),
            'end_datetime' => $this->parseGoogleDateTime($googleEvent->getEnd()),
            'timezone' => $googleEvent->getStart()->getTimeZone() ?? $calendar->timezone,
            'status' => $this->mapGoogleStatus($googleEvent->getStatus()),
            'external_event_id' => $googleEvent->getId(),
            'external_data' => $googleEvent->toSimpleObject(),
            'last_sync_at' => now(),
            'type' => 'custom', // Default type for external events
            'created_by' => 1 // System user
        ];
        
        if ($existingEvent) {
            $existingEvent->update($eventData);
            return ['created' => 0, 'updated' => 1];
        } else {
            Event::create($eventData);
            return ['created' => 1, 'updated' => 0];
        }
    }
    
    private function parseGoogleDateTime($dateTime)
    {
        if ($dateTime->getDateTime()) {
            return Carbon::parse($dateTime->getDateTime());
        } elseif ($dateTime->getDate()) {
            return Carbon::parse($dateTime->getDate())->startOfDay();
        }
        
        return now();
    }
    
    private function mapGoogleStatus($status)
    {
        return match($status) {
            'confirmed' => 'confirmed',
            'tentative' => 'tentative',
            'cancelled' => 'cancelled',
            default => 'confirmed'
        };
    }
    
    public function disconnectCalendar(Calendar $calendar)
    {
        try {
            $calendar->update([
                'allow_external_sync' => false,
                'external_calendar_id' => null,
                'external_credentials' => null,
                'sync_status' => 'disconnected',
                'last_sync_at' => null,
                'sync_error' => null
            ]);
            
            // Optionally remove external_event_id from local events
            $calendar->events()->update([
                'external_event_id' => null,
                'last_sync_at' => null
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to disconnect calendar: ' . $e->getMessage(), [
                'calendar_id' => $calendar->id
            ]);
            
            return false;
        }
    }
    
    public function forceSync(Calendar $calendar)
    {
        // Reset sync status and force a full sync
        $calendar->update([
            'last_sync_at' => null,
            'sync_status' => 'pending'
        ]);
        
        return $this->syncCalendar($calendar);
    }
}