<?php

namespace App\Console;

use App\Console\Commands\ProcessEventRemindersCommand;
use App\Console\Commands\SyncCalendarsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process event reminders every 5 minutes (async for better performance)
        $schedule->command('calendar:process-reminders --async')
                 ->everyFiveMinutes()
                 ->withoutOverlapping(120) // 2 minutes timeout
                 ->runInBackground()
                 ->emailOutputOnFailure(config('mail.admin_email'))
                 ->appendOutputTo(storage_path('logs/reminders.log'))
                 ->description('Process event reminders asynchronously');
        
        // Sync calendars every hour
        $schedule->command('calendar:sync-all')
                 ->hourly()
                 ->withoutOverlapping(300) // 5 minutes timeout
                 ->runInBackground()
                 ->emailOutputOnFailure(config('mail.admin_email'))
                 ->appendOutputTo(storage_path('logs/calendar-sync.log'))
                 ->description('Sync all school calendars with Google Calendar');
        
        // Create birthday reminders daily at 6 AM
        $schedule->command('calendar:process-reminders --birthdays --async')
                 ->dailyAt('06:00')
                 ->withoutOverlapping(600) // 10 minutes timeout
                 ->runInBackground()
                 ->emailOutputOnFailure(config('mail.admin_email'))
                 ->appendOutputTo(storage_path('logs/birthday-reminders.log'))
                 ->description('Process birthday reminders');
        
        // Process immediate reminders every minute (high priority)
        $schedule->command('calendar:process-reminders --force')
                 ->everyMinute()
                 ->withoutOverlapping(60)
                 ->runInBackground()
                 ->between('06:00', '22:00') // Only during active hours
                 ->appendOutputTo(storage_path('logs/immediate-reminders.log'))
                 ->description('Process immediate/urgent reminders');
        
        // Weekly maintenance tasks
        $schedule->call(function () {
            // Clean up old processed reminders
            \Illuminate\Support\Facades\DB::table('processed_reminders')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();
                
            // Clean up old notification logs
            \Illuminate\Support\Facades\DB::table('notification_logs')
                ->where('created_at', '<', now()->subDays(90))
                ->delete();
        })->weekly()
          ->sundays()
          ->at('02:00')
          ->description('Clean up old reminder and notification data');
        
        // Clean up old logs weekly
        $schedule->command('log:clear --days=30')
                 ->weekly()
                 ->sundays()
                 ->at('03:00')
                 ->description('Clean up old application logs');
        
        // Health check for reminder system (every 30 minutes)
        $schedule->call(function () {
            $failureRate = \Illuminate\Support\Facades\Cache::get('reminder_failure_rate', 0);
            if ($failureRate > 10) { // More than 10% failure rate
                \Illuminate\Support\Facades\Log::warning('High reminder failure rate detected', [
                    'failure_rate' => $failureRate,
                    'timestamp' => now()
                ]);
            }
        })->everyThirtyMinutes()
          ->description('Monitor reminder system health');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}