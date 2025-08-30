<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use App\Jobs\ReminderProcessingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessEventRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:process-reminders 
                            {--school-id= : Process reminders for specific school only}
                            {--dry-run : Show what would be processed without sending reminders}
                            {--force : Force processing even if already processed recently}
                            {--async : Process reminders asynchronously using jobs}
                            {--birthdays : Process birthday reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send event reminders';

    /**
     * Execute the console command.
     */
    public function handle(ReminderService $reminderService): int
    {
        $schoolId = $this->option('school-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $async = $this->option('async');
        $processBirthdays = $this->option('birthdays') || $force;
        
        $startTime = now();
        $this->info('Starting event reminders processing at ' . $startTime->format('Y-m-d H:i:s'));
        
        if ($schoolId) {
            $this->info("Processing reminders for school ID: {$schoolId}");
        } else {
            $this->info('Processing reminders for all schools');
        }
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No reminders will be sent');
            return $this->dryRunProcess($reminderService, $schoolId);
        }
        
        if ($async) {
            return $this->processAsync($schoolId, $processBirthdays);
        }
        
        return $this->processSync($reminderService, $schoolId, $processBirthdays, $force, $startTime);
    }
    
    /**
     * Process reminders synchronously
     */
    private function processSync(ReminderService $reminderService, $schoolId, $processBirthdays, $force, $startTime): int
    {
        try {
            $totalStats = [
                'reminders_sent' => 0,
                'failed_reminders' => 0,
                'events_processed' => 0,
                'attendees_notified' => 0,
                'birthday_reminders' => 0,
                'failed_birthdays' => 0
            ];
            
            // Process event reminders
            $this->info('Processing event reminders...');
            $eventStats = $reminderService->processEventReminders($schoolId);
            $totalStats = array_merge($totalStats, $eventStats);
            $this->info('✓ Event reminders processed successfully.');
            
            // Process birthday reminders if requested
            if ($processBirthdays) {
                $this->info('Creating birthday reminders...');
                $birthdayStats = $reminderService->createBirthdayReminders($schoolId);
                $totalStats['birthday_reminders'] = $birthdayStats['birthday_reminders'];
                $totalStats['failed_birthdays'] = $birthdayStats['failed_birthdays'];
                $this->info('✓ Birthday reminders created successfully.');
            }
            
            // Show statistics
            $this->displayProcessingStats($totalStats);
            
            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);
            
            $this->info("\n✅ Reminder processing completed successfully in {$duration} seconds.");
            
            Log::info('Event reminders command completed successfully', [
                'duration_seconds' => $duration,
                'school_id' => $schoolId,
                'dry_run' => false,
                'stats' => $totalStats
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Error processing reminders: ' . $e->getMessage());
            
            Log::error('Event reminders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'school_id' => $schoolId
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Process reminders asynchronously using jobs
     */
    private function processAsync($schoolId, $processBirthdays): int
    {
        try {
            $this->info('Dispatching reminder processing job...');
            
            ReminderProcessingJob::dispatch($schoolId, $processBirthdays);
            
            $this->info('Reminder processing job dispatched successfully!');
            $this->info('Check the queue logs for processing results.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch reminder processing job', [
                'error' => $e->getMessage(),
                'school_id' => $schoolId,
                'process_birthdays' => $processBirthdays
            ]);
            
            $this->error('Failed to dispatch job: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Perform a dry run to show what would be processed
     */
    private function dryRunProcess(ReminderService $reminderService, $schoolId): int
    {
        try {
            $this->showUpcomingReminders();
            $this->showUpcomingBirthdays();
            $this->showReminderStats($reminderService);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Dry run failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display processing statistics
     */
    private function displayProcessingStats(array $stats)
    {
        $this->info("\n📊 Processing Statistics:");
        $this->info("   • Events processed: {$stats['events_processed']}");
        $this->info("   • Reminders sent: {$stats['reminders_sent']}");
        $this->info("   • Failed reminders: {$stats['failed_reminders']}");
        $this->info("   • Attendees notified: {$stats['attendees_notified']}");
        $this->info("   • Birthday reminders: {$stats['birthday_reminders']}");
        $this->info("   • Failed birthdays: {$stats['failed_birthdays']}");
    }
    
    /**
     * Show upcoming reminders in dry-run mode
     */
    private function showUpcomingReminders()
    {
        $this->info('\n📅 Upcoming Event Reminders (Next 7 days):');
        
        $events = \App\Models\Event::with(['calendar', 'eventAttendees'])
            // ->where('send_notifications', true) // Column doesn't exist
            ->where('start_date', '>', now())
            ->where('start_date', '<=', now()->addDays(7))
            ->orderBy('start_date')
            ->when($this->option('school-id'), function($query, $schoolId) {
                return $query->where('school_id', $schoolId);
            })
            // ->orderBy('start_datetime') // Already ordered by start_date above
            ->get();
            
        if ($events->isEmpty()) {
            $this->info('No upcoming events with reminders found.');
            return;
        }
        
        $this->info("Found {$events->count()} upcoming events with reminders:");
        
        $headers = ['Event', 'Date/Time', 'Attendees', 'Reminders'];
        $rows = [];
        
        foreach ($events as $event) {
            $attendeesCount = $event->eventAttendees->where('send_reminders', true)->count();
            $remindersCount = count($event->reminders ?? []);
            
            $rows[] = [
                $event->title,
                $event->start_date->format('d/m/Y H:i'),
                $attendeesCount,
                $remindersCount
            ];
        }
        
        $this->table($headers, $rows);
    }
    
    /**
     * Show upcoming birthdays in dry-run mode
     */
    private function showUpcomingBirthdays()
    {
        $this->info('\n🎂 Upcoming Birthdays (Next 7 days):');
        
        // This would normally query the authentication service for actual birthdays
        // For now, showing that birthday reminders would be processed
        $this->info('Would check for upcoming birthdays and create events if needed.');
        $this->info('Birthday events would be created for users with birthdays in the next 7 days.');
    }
    
    /**
     * Show reminder statistics
     */
    private function showReminderStats(ReminderService $reminderService)
    {
        $schoolId = $this->option('school-id');
        $startDate = now()->startOfDay();
        $endDate = now()->addDays(7)->endOfDay();
        
        try {
            $stats = $reminderService->getReminderStats($schoolId, $startDate, $endDate);
            
            $this->info('\n📊 Reminder Statistics (Next 7 days)' . ($schoolId ? " (School ID: {$schoolId})" : ' (All Schools)') . ':');
            $this->info("   • Total events: {$stats['total_events']}");
            $this->info("   • Events with reminders: {$stats['events_with_reminders']}");
            $this->info("   • Total attendees: {$stats['total_attendees']}");
            $this->info("   • Attendees with reminders: {$stats['attendees_with_reminders']}");
            $this->info("   • Reminders sent today: {$stats['reminders_sent_today']}");
            
            if (isset($stats['events_by_type'])) {
                $this->info('\n   Events by type:');
                foreach ($stats['events_by_type'] as $type => $count) {
                    $this->info("     - {$type}: {$count}");
                }
            }
            
        } catch (\Exception $e) {
            $this->warn('Could not retrieve statistics: ' . $e->getMessage());
        }
    }
}