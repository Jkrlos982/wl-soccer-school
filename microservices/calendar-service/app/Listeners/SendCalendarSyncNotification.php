<?php

namespace App\Listeners;

use App\Events\CalendarSyncCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class SendCalendarSyncNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CalendarSyncCompleted $event): void
    {
        try {
            $calendar = $event->calendar;
            $user = User::find($calendar->user_id);
            
            if (!$user) {
                Log::warning('User not found for calendar sync notification', [
                    'calendar_id' => $calendar->id,
                    'user_id' => $calendar->user_id
                ]);
                return;
            }

            // Log the sync completion
            Log::info('Calendar sync completed', [
                'calendar_id' => $calendar->id,
                'calendar_name' => $calendar->name,
                'user_id' => $user->id,
                'success' => $event->success,
                'stats' => $event->stats,
                'error' => $event->error
            ]);

            // Send notification based on sync result
            if ($event->success) {
                $this->handleSuccessfulSync($user, $calendar, $event->stats);
            } else {
                $this->handleFailedSync($user, $calendar, $event->error);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send calendar sync notification', [
                'calendar_id' => $event->calendar->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle successful sync notification.
     */
    private function handleSuccessfulSync(User $user, $calendar, array $stats): void
    {
        // Only send notification for significant syncs or if explicitly requested
        $eventsCreated = $stats['events_created'] ?? 0;
        $eventsUpdated = $stats['events_updated'] ?? 0;
        $eventsDeleted = $stats['events_deleted'] ?? 0;
        
        $totalChanges = $eventsCreated + $eventsUpdated + $eventsDeleted;
        
        // Send notification if there were significant changes
        if ($totalChanges > 0) {
            Log::info('Sending calendar sync success notification', [
                'user_id' => $user->id,
                'calendar_id' => $calendar->id,
                'changes' => $totalChanges,
                'stats' => $stats
            ]);
            
            // Here you would send actual notification
            // For example: $user->notify(new CalendarSyncSuccessNotification($calendar, $stats));
        }
    }

    /**
     * Handle failed sync notification.
     */
    private function handleFailedSync(User $user, $calendar, ?string $error): void
    {
        Log::warning('Sending calendar sync failure notification', [
            'user_id' => $user->id,
            'calendar_id' => $calendar->id,
            'error' => $error
        ]);
        
        // Here you would send actual notification
        // For example: $user->notify(new CalendarSyncFailedNotification($calendar, $error));
    }

    /**
     * Handle a job failure.
     */
    public function failed(CalendarSyncCompleted $event, \Exception $exception): void
    {
        Log::error('Calendar sync notification listener failed permanently', [
            'calendar_id' => $event->calendar->id,
            'user_id' => $event->calendar->user_id,
            'error' => $exception->getMessage()
        ]);
    }
}
