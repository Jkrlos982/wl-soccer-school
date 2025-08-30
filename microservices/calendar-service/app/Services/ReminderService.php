<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Calendar;
use App\Models\NotificationTemplate;
use App\Events\RemindersProcessed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ReminderService
{
    private NotificationService $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    public function processEventReminders($schoolId = null)
    {
        $stats = [
            'reminders_sent' => 0,
            'failed_reminders' => 0,
            'events_processed' => 0,
            'attendees_notified' => 0
        ];
        
        $query = Event::with(['calendar', 'eventAttendees'])
            // ->where('send_notifications', true) // Column doesn't exist
            ->where('start_date', '>', now())
            ->where('start_date', '<=', now()->addDays(7));
            
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        $upcomingEvents = $query->get();
        
        foreach ($upcomingEvents as $event) {
            $eventStats = $this->processEventReminder($event);
            $stats['reminders_sent'] += $eventStats['sent'];
            $stats['failed_reminders'] += $eventStats['failed'];
            $stats['events_processed']++;
            $stats['attendees_notified'] += $eventStats['attendees'];
        }
        
        // Dispatch event with statistics
        event(new RemindersProcessed($schoolId, $stats, false));
        
        return $stats;
    }
    
    private function processEventReminder(Event $event)
    {
        $stats = ['sent' => 0, 'failed' => 0, 'attendees' => 0];
        
        $reminders = $event->reminders ?? $this->getDefaultReminders($event->type);
        
        foreach ($reminders as $reminder) {
            $reminderTime = $event->start_date->subMinutes($reminder['minutes']);
            
            // Check if it's time to send this reminder
            if (now()->between($reminderTime->subMinutes(5), $reminderTime->addMinutes(5))) {
                $reminderStats = $this->sendEventReminder($event, $reminder);
                $stats['sent'] += $reminderStats['sent'];
                $stats['failed'] += $reminderStats['failed'];
                $stats['attendees'] += $reminderStats['attendees'];
            }
        }
        
        return $stats;
    }
    
    private function sendEventReminder(Event $event, $reminder)
    {
        $stats = ['sent' => 0, 'failed' => 0, 'attendees' => 0];
        
        // Get attendees who should receive reminders
        $attendees = $event->eventAttendees()
            ->where('send_reminders', true)
            ->where(function($query) use ($reminder) {
                $query->whereNull('last_reminder_sent')
                      ->orWhere('last_reminder_sent', '<', now()->subHours(1));
            })
            ->get();
            
        $stats['attendees'] = $attendees->count();
        
        foreach ($attendees as $attendee) {
            if ($this->sendReminderToAttendee($event, $attendee, $reminder)) {
                $stats['sent']++;
            } else {
                $stats['failed']++;
            }
        }
        
        return $stats;
    }
    
    private function sendReminderToAttendee(Event $event, EventAttendee $attendee, $reminder)
    {
        $variables = [
            'event_title' => $event->title,
            'event_date' => $event->start_date->format('d/m/Y'),
            'event_time' => $event->start_date->format('H:i'),
            'event_location' => $event->location ?? 'Por definir',
            'attendee_name' => $attendee->attendee_name,
            'reminder_time' => $this->formatReminderTime($reminder['minutes']),
            'calendar_name' => $event->calendar->name
        ];
        
        // Determine notification type based on reminder method
        $notificationType = $reminder['method'] ?? 'whatsapp';
        
        // Get appropriate template
        $template = $this->getEventReminderTemplate($event->type, $notificationType);
        
        if ($template) {
            try {
                $success = $this->notificationService->send(
                    'whatsapp', // Default channel
                    [
                        'id' => $attendee->attendee_id,
                        'phone' => $attendee->attendee_phone,
                        'email' => $attendee->attendee_email,
                        'name' => $attendee->attendee_name,
                    ],
                    $template->code,
                    $variables,
                    $event->school_id
                );
                
                // Update last reminder sent
                $attendee->update(['last_reminder_sent' => now()]);
                
                Log::info('Event reminder sent successfully', [
                    'event_id' => $event->id,
                    'attendee_id' => $attendee->id,
                    'reminder_type' => $reminder['type'] ?? 'general'
                ]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to send event reminder', [
                    'event_id' => $event->id,
                    'attendee_id' => $attendee->id,
                    'error' => $e->getMessage()
                ]);
                
                return false;
            }
        }
        
        return false;
    }
    
    private function getDefaultReminders($eventType)
    {
        return match($eventType) {
            'training' => [
                ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'], // 1 day
                ['method' => 'whatsapp', 'minutes' => 60, 'type' => 'hour_before']   // 1 hour
            ],
            'match' => [
                ['method' => 'whatsapp', 'minutes' => 2880, 'type' => 'two_days_before'], // 2 days
                ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'],     // 1 day
                ['method' => 'whatsapp', 'minutes' => 120, 'type' => 'two_hours_before'] // 2 hours
            ],
            'tournament' => [
                ['method' => 'whatsapp', 'minutes' => 10080, 'type' => 'week_before'],    // 1 week
                ['method' => 'whatsapp', 'minutes' => 2880, 'type' => 'two_days_before'], // 2 days
                ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before']       // 1 day
            ],
            'meeting' => [
                ['method' => 'email', 'minutes' => 1440, 'type' => 'day_before'],    // 1 day
                ['method' => 'whatsapp', 'minutes' => 30, 'type' => 'thirty_minutes'] // 30 min
            ],
            'payment_due' => [
                ['method' => 'whatsapp', 'minutes' => 4320, 'type' => 'three_days_before'], // 3 days
                ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'],        // 1 day
                ['method' => 'whatsapp', 'minutes' => 0, 'type' => 'due_date']              // Due date
            ],
            default => [
                ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'], // 1 day
                ['method' => 'whatsapp', 'minutes' => 60, 'type' => 'hour_before']   // 1 hour
            ]
        };
    }
    
    private function getEventReminderTemplate($eventType, $notificationType)
    {
        // Get template based on event type and notification method
        $templateCode = match($eventType) {
            'training' => 'training_reminder',
            'match' => 'match_reminder',
            'tournament' => 'tournament_reminder',
            'meeting' => 'meeting_reminder',
            'payment_due' => 'payment_reminder',
            'birthday' => 'birthday_reminder',
            default => 'general_event_reminder'
        };
        
        return NotificationTemplate::where('code', $templateCode)
            ->where('type', $notificationType)
            ->where('is_active', true)
            ->first();
    }
    
    private function formatReminderTime($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' minutos';
        } elseif ($minutes < 1440) {
            $hours = intval($minutes / 60);
            return $hours . ($hours === 1 ? ' hora' : ' horas');
        } else {
            $days = intval($minutes / 1440);
            return $days . ($days === 1 ? ' día' : ' días');
        }
    }
    
    public function createBirthdayReminders($schoolId = null)
    {
        $stats = [
            'birthday_reminders' => 0,
            'failed_birthdays' => 0
        ];
        
        // Get users with birthdays in the next 7 days
        $upcomingBirthdays = $this->getUsersWithUpcomingBirthdays($schoolId);
        
        foreach ($upcomingBirthdays as $user) {
            if ($this->createBirthdayEvent($user)) {
                $stats['birthday_reminders']++;
            } else {
                $stats['failed_birthdays']++;
            }
        }
        
        // Dispatch event with birthday statistics
        event(new RemindersProcessed($schoolId, $stats, true));
        
        return $stats;
    }
    
    private function getUsersWithUpcomingBirthdays($schoolId = null)
    {
        try {
            $params = ['days' => 7];
            
            if ($schoolId) {
                $params['school_id'] = $schoolId;
            }
            
            // Call to auth service to get users with upcoming birthdays
            $response = Http::timeout(30)->get(config('services.auth.url') . '/api/users/upcoming-birthdays', $params);
            
            if ($response->successful()) {
                return $response->json('data', []);
            }
            
            Log::warning('Failed to fetch upcoming birthdays from auth service', [
                'status' => $response->status(),
                'response' => $response->body(),
                'school_id' => $schoolId
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching upcoming birthdays', [
                'error' => $e->getMessage(),
                'school_id' => $schoolId
            ]);
            
            return [];
        }
    }
    
    private function createBirthdayEvent($user)
    {
        $birthdayDate = Carbon::parse($user['birthday'])->setYear(now()->year);
        
        // If birthday already passed this year, set for next year
        if ($birthdayDate->isPast()) {
            $birthdayDate->addYear();
        }
        
        // Check if birthday event already exists
        $existingEvent = Event::where('reference_type', 'Birthday')
            ->where('reference_id', $user['id'])
            ->whereDate('start_date', $birthdayDate)
            ->first();
            
        if (!$existingEvent) {
            try {
                $defaultCalendar = $this->getDefaultCalendar($user['school_id']);
                
                if ($defaultCalendar) {
                    Event::create([
                        'calendar_id' => $defaultCalendar->id,
                        'school_id' => $user['school_id'],
                        'title' => '🎂 Cumpleaños de ' . $user['name'],
                        'description' => 'Cumpleaños de ' . $user['name'],
                        'start_date' => $birthdayDate->startOfDay(),
                        'end_date' => $birthdayDate->endOfDay(),
                        'is_all_day' => true,
                        'type' => 'birthday',
                        'status' => 'confirmed',
                        'visibility' => 'public',
                        'reference_type' => 'Birthday',
                        'reference_id' => $user['id'],
                        'reminders' => [
                            ['method' => 'whatsapp', 'minutes' => 0, 'type' => 'birthday']
                        ],
                        'created_by' => 1 // System user
                    ]);
                    
                    Log::info('Birthday event created successfully', [
                        'user_id' => $user['id'],
                        'user_name' => $user['name'],
                        'birthday_date' => $birthdayDate->toDateString()
                    ]);
                    
                    return true;
                } else {
                    Log::warning('No default calendar found for school', [
                        'school_id' => $user['school_id'],
                        'user_id' => $user['id']
                    ]);
                    return false;
                }
            } catch (\Exception $e) {
                Log::error('Failed to create birthday event', [
                    'user_id' => $user['id'],
                    'user_name' => $user['name'],
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        return true; // Event already exists
    }
    
    private function getDefaultCalendar($schoolId)
    {
        return Calendar::where('school_id', $schoolId)
            ->where('is_default', true)
            ->first();
    }
    
    /**
     * Send immediate reminder for a specific event
     */
    public function sendImmediateReminder(Event $event, array $attendeeIds = [])
    {
        $query = $event->eventAttendees()->where('send_reminders', true);
        
        if (!empty($attendeeIds)) {
            $query->whereIn('id', $attendeeIds);
        }
        
        $attendees = $query->get();
        
        $reminder = [
            'method' => 'whatsapp',
            'minutes' => 0,
            'type' => 'immediate'
        ];
        
        foreach ($attendees as $attendee) {
            $this->sendReminderToAttendee($event, $attendee, $reminder);
        }
        
        return $attendees->count();
    }
    
    /**
     * Get reminder statistics
     */
    public function getReminderStats($schoolId = null, $dateFrom = null, $dateTo = null)
    {
        $query = Event::query();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($dateFrom) {
            $query->where('start_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('start_date', '<=', $dateTo);
        }
        
        $events = $query->with('eventAttendees')->get();
        
        $stats = [
            'total_events' => $events->count(),
            'events_with_reminders' => $events->count(),
            'total_attendees' => $events->sum(function($event) {
                return $event->eventAttendees->count();
            }),
            'attendees_with_reminders' => $events->sum(function($event) {
                return $event->eventAttendees->where('send_reminders', true)->count();
            }),
            'reminders_sent_today' => $events->sum(function($event) {
                return $event->eventAttendees
                    ->where('last_reminder_sent', '>=', now()->startOfDay())
                    ->count();
            })
        ];
        
        return $stats;
    }
}