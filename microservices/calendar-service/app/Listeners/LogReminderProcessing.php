<?php

namespace App\Listeners;

use App\Events\RemindersProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LogReminderProcessing implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(RemindersProcessed $event): void
    {
        // Log the reminder processing statistics
        Log::info('Reminders processed', [
            'school_id' => $event->schoolId,
            'stats' => $event->stats,
            'process_birthdays' => $event->processBirthdays,
            'timestamp' => $event->timestamp
        ]);

        // Store daily statistics in cache
        $this->storeDailyStats($event);

        // Check for failures and log warnings if needed
        $this->checkForFailures($event);

        // Update system health metrics
        $this->updateHealthMetrics($event);
    }

    /**
     * Store daily statistics in cache for reporting.
     */
    private function storeDailyStats(RemindersProcessed $event): void
    {
        $date = $event->timestamp->format('Y-m-d');
        $cacheKey = "reminder_stats:{$date}";
        
        if ($event->schoolId) {
            $cacheKey .= ":school:{$event->schoolId}";
        }

        $currentStats = Cache::get($cacheKey, [
            'reminders_sent' => 0,
            'failed_reminders' => 0,
            'birthday_reminders' => 0,
            'processing_runs' => 0
        ]);

        $newStats = [
            'reminders_sent' => $currentStats['reminders_sent'] + ($event->stats['reminders_sent'] ?? 0),
            'failed_reminders' => $currentStats['failed_reminders'] + ($event->stats['failed_reminders'] ?? 0),
            'birthday_reminders' => $currentStats['birthday_reminders'] + ($event->stats['birthday_reminders'] ?? 0),
            'processing_runs' => $currentStats['processing_runs'] + 1,
            'last_run' => $event->timestamp->toISOString()
        ];

        // Store for 30 days
        Cache::put($cacheKey, $newStats, now()->addDays(30));
    }

    /**
     * Check for failures and log warnings.
     */
    private function checkForFailures(RemindersProcessed $event): void
    {
        $failed = $event->stats['failed_reminders'] ?? 0;
        $total = ($event->stats['reminders_sent'] ?? 0) + $failed;
        
        if ($failed > 0 && $total > 0) {
            $failureRate = ($failed / $total) * 100;
            
            if ($failureRate > 10) { // More than 10% failure rate
                Log::warning('High reminder failure rate detected', [
                    'school_id' => $event->schoolId,
                    'failure_rate' => round($failureRate, 2),
                    'failed_count' => $failed,
                    'total_count' => $total,
                    'stats' => $event->stats
                ]);
            }
        }
    }

    /**
     * Update system health metrics.
     */
    private function updateHealthMetrics(RemindersProcessed $event): void
    {
        $healthKey = 'reminder_system_health';
        
        $health = Cache::get($healthKey, [
            'last_successful_run' => null,
            'consecutive_failures' => 0,
            'total_runs_today' => 0
        ]);

        $failed = $event->stats['failed_reminders'] ?? 0;
        $sent = $event->stats['reminders_sent'] ?? 0;
        
        if ($sent > 0 || $failed === 0) {
            $health['last_successful_run'] = $event->timestamp->toISOString();
            $health['consecutive_failures'] = 0;
        } else {
            $health['consecutive_failures']++;
        }
        
        $health['total_runs_today']++;
        
        // Reset daily counter at midnight
        if ($event->timestamp->format('H:i') === '00:00') {
            $health['total_runs_today'] = 1;
        }
        
        Cache::put($healthKey, $health, now()->addDay());
        
        // Alert if too many consecutive failures
        if ($health['consecutive_failures'] >= 5) {
            Log::error('Reminder system health alert: Multiple consecutive failures', [
                'consecutive_failures' => $health['consecutive_failures'],
                'last_successful_run' => $health['last_successful_run']
            ]);
        }
    }
}