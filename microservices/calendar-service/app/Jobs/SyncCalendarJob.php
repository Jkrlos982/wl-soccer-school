<?php

namespace App\Jobs;

use App\Models\Calendar;
use App\Services\CalendarSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncCalendarJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * The calendar to sync.
     *
     * @var Calendar
     */
    protected $calendar;

    /**
     * Whether to force sync even if recently synced.
     *
     * @var bool
     */
    protected $forceSync;

    /**
     * Create a new job instance.
     */
    public function __construct(Calendar $calendar, bool $forceSync = false)
    {
        $this->calendar = $calendar;
        $this->forceSync = $forceSync;
        
        // Set queue based on priority
        $this->onQueue($forceSync ? 'high' : 'default');
    }

    /**
     * Execute the job.
     */
    public function handle(CalendarSyncService $syncService): void
    {
        try {
            Log::info('Starting calendar sync job', [
                'calendar_id' => $this->calendar->id,
                'calendar_name' => $this->calendar->name,
                'force_sync' => $this->forceSync,
                'attempt' => $this->attempts()
            ]);

            // Check if calendar still exists and is configured for sync
            $calendar = $this->calendar->fresh();
            
            if (!$calendar) {
                Log::warning('Calendar not found during sync job', [
                    'calendar_id' => $this->calendar->id
                ]);
                return;
            }

            if (!$calendar->allow_external_sync || !$calendar->external_calendar_id) {
                Log::info('Calendar not configured for external sync', [
                    'calendar_id' => $calendar->id,
                    'allow_external_sync' => $calendar->allow_external_sync,
                    'external_calendar_id' => $calendar->external_calendar_id
                ]);
                return;
            }

            // Perform the sync
            $result = $this->forceSync 
                ? $syncService->forceSync($calendar)
                : $syncService->syncCalendar($calendar);

            if ($result) {
                Log::info('Calendar sync job completed successfully', [
                    'calendar_id' => $calendar->id,
                    'calendar_name' => $calendar->name
                ]);
            } else {
                Log::error('Calendar sync job failed', [
                    'calendar_id' => $calendar->id,
                    'calendar_name' => $calendar->name,
                    'sync_error' => $calendar->fresh()->sync_error
                ]);
                
                // If this is the last attempt, mark as failed
                if ($this->attempts() >= $this->tries) {
                    $calendar->update([
                        'sync_status' => 'error',
                        'sync_error' => 'Sync job failed after ' . $this->tries . ' attempts'
                    ]);
                }
                
                throw new Exception('Calendar sync failed: ' . $calendar->fresh()->sync_error);
            }

        } catch (Exception $e) {
            Log::error('Calendar sync job exception', [
                'calendar_id' => $this->calendar->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries
            ]);

            // If this is the last attempt, update calendar status
            if ($this->attempts() >= $this->tries) {
                $this->calendar->fresh()->update([
                    'sync_status' => 'error',
                    'sync_error' => 'Job failed: ' . $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Calendar sync job permanently failed', [
            'calendar_id' => $this->calendar->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update calendar status to indicate permanent failure
        $this->calendar->fresh()->update([
            'sync_status' => 'error',
            'sync_error' => 'Permanent failure: ' . $exception->getMessage()
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 120, 300]; // 30 seconds, 2 minutes, 5 minutes
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'calendar-sync',
            'calendar:' . $this->calendar->id,
            'user:' . $this->calendar->user_id
        ];
    }
}
