<?php

namespace App\Jobs;

use App\Services\ReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReminderProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, and 5 minutes

    protected $schoolId;
    protected $processBirthdays;

    /**
     * Create a new job instance.
     */
    public function __construct($schoolId = null, $processBirthdays = false)
    {
        $this->schoolId = $schoolId;
        $this->processBirthdays = $processBirthdays;
        $this->onQueue('reminders');
    }

    /**
     * Execute the job.
     */
    public function handle(ReminderService $reminderService): void
    {
        try {
            Log::info('Starting reminder processing job', [
                'school_id' => $this->schoolId,
                'process_birthdays' => $this->processBirthdays
            ]);

            // Process event reminders
            $reminderService->processEventReminders();

            // Process birthday reminders if requested
            if ($this->processBirthdays) {
                $reminderService->createBirthdayReminders();
            }

            Log::info('Reminder processing job completed successfully', [
                'school_id' => $this->schoolId,
                'process_birthdays' => $this->processBirthdays
            ]);

        } catch (\Exception $e) {
            Log::error('Reminder processing job failed', [
                'school_id' => $this->schoolId,
                'process_birthdays' => $this->processBirthdays,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Reminder processing job failed permanently', [
            'school_id' => $this->schoolId,
            'process_birthdays' => $this->processBirthdays,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        $tags = ['reminders'];
        
        if ($this->schoolId) {
            $tags[] = "school:{$this->schoolId}";
        }
        
        if ($this->processBirthdays) {
            $tags[] = 'birthdays';
        }
        
        return $tags;
    }
}