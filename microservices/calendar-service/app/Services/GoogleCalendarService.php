<?php

namespace App\Services;

use App\Models\Event;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventReminder;
use Google\Service\Calendar\EventReminders;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleCalendarService
{
    private $client;
    private $service;
    
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('google-calendar.oauth.client_id'));
        $this->client->setClientSecret(config('google-calendar.oauth.client_secret'));
        $this->client->setRedirectUri(config('google-calendar.oauth.redirect_uri'));
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        $this->service = new Calendar($this->client);
    }
    
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }
    
    public function handleCallback($code)
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                throw new Exception('Error getting access token: ' . $token['error']);
            }
            
            return $token;
        } catch (Exception $e) {
            Log::error('Google Calendar auth error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
        
        // Refresh token if needed
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken();
                return $newToken;
            }
        }
        
        return $token;
    }
    
    public function createEvent($calendarId, Event $event)
    {
        try {
            $googleEvent = new GoogleEvent([
                'summary' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'start' => [
                    'dateTime' => $event->start_datetime->toRfc3339String(),
                    'timeZone' => $event->timezone,
                ],
                'end' => [
                    'dateTime' => $event->end_datetime->toRfc3339String(),
                    'timeZone' => $event->timezone,
                ],
                'attendees' => $this->formatAttendees($event->attendees ?? []),
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => $this->formatReminders($event->reminders ?? [])
                ],
                'colorId' => $this->getGoogleColorId($event->color),
                'visibility' => $event->visibility,
                'status' => $this->mapStatus($event->status)
            ]);
            
            if ($event->is_recurring && $event->recurrence_rule) {
                $googleEvent->setRecurrence([$this->formatRecurrenceRule($event->recurrence_rule)]);
            }
            
            $createdEvent = $this->service->events->insert($calendarId, $googleEvent);
            
            return [
                'success' => true,
                'external_id' => $createdEvent->getId(),
                'response' => $createdEvent
            ];
        } catch (Exception $e) {
            Log::error('Google Calendar create event error: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'calendar_id' => $calendarId
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function updateEvent($calendarId, $eventId, Event $event)
    {
        try {
            $googleEvent = $this->service->events->get($calendarId, $eventId);
            
            $googleEvent->setSummary($event->title);
            $googleEvent->setDescription($event->description);
            $googleEvent->setLocation($event->location);
            
            $start = new EventDateTime();
            $start->setDateTime($event->start_datetime->toRfc3339String());
            $start->setTimeZone($event->timezone);
            $googleEvent->setStart($start);
            
            $end = new EventDateTime();
            $end->setDateTime($event->end_datetime->toRfc3339String());
            $end->setTimeZone($event->timezone);
            $googleEvent->setEnd($end);
            
            $updatedEvent = $this->service->events->update($calendarId, $eventId, $googleEvent);
            
            return [
                'success' => true,
                'response' => $updatedEvent
            ];
        } catch (Exception $e) {
            Log::error('Google Calendar update event error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function deleteEvent($calendarId, $eventId)
    {
        try {
            $this->service->events->delete($calendarId, $eventId);
            
            return ['success' => true];
        } catch (Exception $e) {
            Log::error('Google Calendar delete event error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function syncEvents($calendarId, $syncToken = null)
    {
        try {
            $optParams = [
                'maxResults' => 250,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => now()->subMonths(1)->toRfc3339String(),
                'timeMax' => now()->addMonths(6)->toRfc3339String()
            ];
            
            if ($syncToken) {
                $optParams['syncToken'] = $syncToken;
            }
            
            $events = $this->service->events->listEvents($calendarId, $optParams);
            
            return [
                'success' => true,
                'events' => $events->getItems(),
                'next_sync_token' => $events->getNextSyncToken()
            ];
        } catch (Exception $e) {
            Log::error('Google Calendar sync error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function listCalendars($credentials)
    {
        try {
            $this->setAccessToken($credentials);
            
            $calendarList = $this->service->calendarList->listCalendarList();
            
            $calendars = [];
            foreach ($calendarList->getItems() as $calendar) {
                $calendars[] = [
                    'id' => $calendar->getId(),
                    'summary' => $calendar->getSummary(),
                    'description' => $calendar->getDescription(),
                    'primary' => $calendar->getPrimary(),
                    'access_role' => $calendar->getAccessRole(),
                    'background_color' => $calendar->getBackgroundColor(),
                    'foreground_color' => $calendar->getForegroundColor(),
                ];
            }
            
            return [
                'success' => true,
                'calendars' => $calendars
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function formatAttendees($attendees)
    {
        return array_map(function($attendee) {
            return [
                'email' => $attendee['email'] ?? '',
                'displayName' => $attendee['name'] ?? '',
                'responseStatus' => $attendee['status'] ?? 'needsAction'
            ];
        }, $attendees);
    }
    
    private function formatReminders($reminders)
    {
        return array_map(function($reminder) {
            return [
                'method' => $reminder['method'] ?? 'email',
                'minutes' => $reminder['minutes'] ?? 15
            ];
        }, $reminders);
    }
    
    private function formatRecurrenceRule($rule)
    {
        // Convertir regla interna a formato RRULE de Google
        $rrule = 'RRULE:';
        
        if (isset($rule['freq'])) {
            $rrule .= 'FREQ=' . $rule['freq'];
        }
        
        if (isset($rule['interval'])) {
            $rrule .= ';INTERVAL=' . $rule['interval'];
        }
        
        if (isset($rule['count'])) {
            $rrule .= ';COUNT=' . $rule['count'];
        }
        
        if (isset($rule['until'])) {
            $rrule .= ';UNTIL=' . $rule['until'];
        }
        
        if (isset($rule['byday'])) {
            $rrule .= ';BYDAY=' . implode(',', $rule['byday']);
        }
        
        return $rrule;
    }
    
    private function getGoogleColorId($hexColor)
    {
        // Mapear colores hex a IDs de color de Google Calendar
        $colorMap = [
            '#a4bdfc' => '1', // Lavender
            '#7ae7bf' => '2', // Sage
            '#dbadff' => '3', // Grape
            '#ff887c' => '4', // Flamingo
            '#fbd75b' => '5', // Banana
            '#ffb878' => '6', // Tangerine
            '#46d6db' => '7', // Peacock
            '#e1e1e1' => '8', // Graphite
            '#5484ed' => '9', // Blueberry
            '#51b749' => '10', // Basil
            '#dc2127' => '11'  // Tomato
        ];
        
        return $colorMap[$hexColor] ?? '9'; // Default to blueberry
    }
    
    private function mapStatus($status)
    {
        return match($status) {
            'confirmed' => 'confirmed',
            'tentative' => 'tentative',
            'cancelled' => 'cancelled',
            default => 'confirmed'
        };
    }
}