<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\CalendarSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncCalendarsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync 
                            {--calendar= : Specific calendar ID to sync}
                            {--force : Force sync even if recently synced}
                            {--dry-run : Show what would be synced without actually syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize calendars with Google Calendar';

    private $syncService;

    public function __construct(CalendarSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting calendar synchronization...');
        
        $calendarId = $this->option('calendar');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual synchronization will occur');
        }
        
        try {
            if ($calendarId) {
                // Sync specific calendar
                $this->syncSpecificCalendar($calendarId, $force, $dryRun);
            } else {
                // Sync all calendars that need synchronization
                $this->syncAllCalendars($force, $dryRun);
            }
            
            $this->info('Calendar synchronization completed successfully!');
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('Calendar synchronization failed: ' . $e->getMessage());
            Log::error('Calendar sync command failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function syncSpecificCalendar($calendarId, $force, $dryRun)
    {
        $calendar = Calendar::find($calendarId);
        
        if (!$calendar) {
            $this->error("Calendar with ID {$calendarId} not found.");
            return;
        }
        
        if (!$calendar->allow_external_sync || !$calendar->external_calendar_id) {
            $this->warn("Calendar '{$calendar->name}' is not configured for external sync.");
            return;
        }
        
        $this->info("Syncing calendar: {$calendar->name}");
        
        if ($dryRun) {
            $this->line("  - Would sync calendar ID: {$calendar->id}");
            $this->line("  - External calendar ID: {$calendar->external_calendar_id}");
            $this->line("  - Last sync: {$calendar->last_sync_at}");
            return;
        }
        
        $result = $force 
            ? $this->syncService->forceSync($calendar)
            : $this->syncService->syncCalendar($calendar);
            
        if ($result) {
            $this->info("  ✓ Successfully synced calendar: {$calendar->name}");
        } else {
            $this->error("  ✗ Failed to sync calendar: {$calendar->name}");
            if ($calendar->sync_error) {
                $this->error("    Error: {$calendar->sync_error}");
            }
        }
    }
    
    private function syncAllCalendars($force, $dryRun)
    {
        $query = Calendar::where('allow_external_sync', true)
            ->whereNotNull('external_calendar_id')
            ->where('sync_status', '!=', 'error');
            
        if (!$force) {
            // Only sync calendars that haven't been synced recently
            $syncInterval = config('google-calendar.sync.interval', 15);
            $query->where(function ($q) use ($syncInterval) {
                $q->whereNull('last_sync_at')
                  ->orWhere('last_sync_at', '<', now()->subMinutes($syncInterval));
            });
        }
        
        $calendars = $query->get();
        
        if ($calendars->isEmpty()) {
            $this->info('No calendars need synchronization.');
            return;
        }
        
        $this->info("Found {$calendars->count()} calendar(s) to sync.");
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($calendars as $calendar) {
            $this->line("Syncing: {$calendar->name}");
            
            if ($dryRun) {
                $this->line("  - Would sync calendar ID: {$calendar->id}");
                $this->line("  - External calendar ID: {$calendar->external_calendar_id}");
                $this->line("  - Last sync: {$calendar->last_sync_at}");
                continue;
            }
            
            try {
                $result = $force 
                    ? $this->syncService->forceSync($calendar)
                    : $this->syncService->syncCalendar($calendar);
                    
                if ($result) {
                    $this->info("  ✓ Success");
                    $successCount++;
                } else {
                    $this->error("  ✗ Failed");
                    if ($calendar->fresh()->sync_error) {
                        $this->error("    Error: {$calendar->fresh()->sync_error}");
                    }
                    $errorCount++;
                }
            } catch (Exception $e) {
                $this->error("  ✗ Exception: {$e->getMessage()}");
                $errorCount++;
            }
        }
        
        if (!$dryRun) {
            $this->info("\nSynchronization Summary:");
            $this->info("  - Successful: {$successCount}");
            $this->info("  - Failed: {$errorCount}");
            $this->info("  - Total: {$calendars->count()}");
        }
    }
}
