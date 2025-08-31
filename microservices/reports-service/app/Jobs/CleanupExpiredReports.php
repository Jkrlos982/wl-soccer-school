<?php

namespace App\Jobs;

use App\Models\GeneratedReport;
use App\Models\AlertLog;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class CleanupExpiredReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 300; // 5 minutes

    /**
     * Days to keep completed reports
     */
    protected $keepCompletedDays;

    /**
     * Days to keep failed reports
     */
    protected $keepFailedDays;

    /**
     * Days to keep alert logs
     */
    protected $keepAlertLogsDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $keepCompletedDays = 30,
        int $keepFailedDays = 7,
        int $keepAlertLogsDays = 90
    ) {
        $this->keepCompletedDays = $keepCompletedDays;
        $this->keepFailedDays = $keepFailedDays;
        $this->keepAlertLogsDays = $keepAlertLogsDays;
        
        $this->onQueue('cleanup');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting cleanup of expired reports and logs');

        try {
            $cleanupStats = [
                'completed_reports_cleaned' => 0,
                'failed_reports_cleaned' => 0,
                'files_deleted' => 0,
                'alert_logs_cleaned' => 0,
                'temp_files_cleaned' => 0,
                'storage_freed_mb' => 0
            ];

            // Clean up completed reports
            $cleanupStats['completed_reports_cleaned'] = $this->cleanupCompletedReports();

            // Clean up failed reports
            $cleanupStats['failed_reports_cleaned'] = $this->cleanupFailedReports();

            // Clean up orphaned files
            $cleanupStats['files_deleted'] = $this->cleanupOrphanedFiles();

            // Clean up old alert logs
            $cleanupStats['alert_logs_cleaned'] = $this->cleanupAlertLogs();

            // Clean up temporary files
            $cleanupStats['temp_files_cleaned'] = $this->cleanupTempFiles();

            // Calculate storage freed (approximate)
            $cleanupStats['storage_freed_mb'] = $this->calculateStorageFreed($cleanupStats);

            Log::info('Cleanup completed successfully', $cleanupStats);

        } catch (Exception $e) {
            Log::error('Cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Clean up completed reports older than specified days
     */
    protected function cleanupCompletedReports(): int
    {
        $cutoffDate = Carbon::now()->subDays($this->keepCompletedDays);
        
        $expiredReports = GeneratedReport::where('status', 'completed')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($expiredReports as $report) {
            try {
                // Delete the file if it exists
                if ($report->file_path && Storage::disk('reports')->exists($report->file_path)) {
                    Storage::disk('reports')->delete($report->file_path);
                }

                // Delete the database record
                $report->delete();
                $count++;

            } catch (Exception $e) {
                Log::warning('Failed to delete expired report', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Clean up failed reports older than specified days
     */
    protected function cleanupFailedReports(): int
    {
        $cutoffDate = Carbon::now()->subDays($this->keepFailedDays);
        
        $failedReports = GeneratedReport::where('status', 'failed')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($failedReports as $report) {
            try {
                // Delete any partial files
                if ($report->file_path && Storage::disk('reports')->exists($report->file_path)) {
                    Storage::disk('reports')->delete($report->file_path);
                }

                // Delete the database record
                $report->delete();
                $count++;

            } catch (Exception $e) {
                Log::warning('Failed to delete failed report', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Clean up orphaned files (files without database records)
     */
    protected function cleanupOrphanedFiles(): int
    {
        $count = 0;
        $reportsDisk = Storage::disk('reports');
        
        try {
            $allFiles = $reportsDisk->allFiles();
            $existingPaths = GeneratedReport::whereNotNull('file_path')
                ->pluck('file_path')
                ->toArray();

            foreach ($allFiles as $filePath) {
                // Skip if file is referenced in database
                if (in_array($filePath, $existingPaths)) {
                    continue;
                }

                // Skip recent files (less than 1 hour old)
                $fileTime = $reportsDisk->lastModified($filePath);
                if ($fileTime && Carbon::createFromTimestamp($fileTime)->gt(Carbon::now()->subHour())) {
                    continue;
                }

                // Delete orphaned file
                if ($reportsDisk->delete($filePath)) {
                    $count++;
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to cleanup orphaned files', [
                'error' => $e->getMessage()
            ]);
        }

        return $count;
    }

    /**
     * Clean up old alert logs
     */
    protected function cleanupAlertLogs(): int
    {
        $cutoffDate = Carbon::now()->subDays($this->keepAlertLogsDays);
        
        return AlertLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFiles(): int
    {
        $count = 0;
        $tempDisk = Storage::disk('temp');
        
        try {
            if (!$tempDisk->exists('/')) {
                return 0;
            }

            $tempFiles = $tempDisk->allFiles();
            $cutoffTime = Carbon::now()->subHours(2); // Clean files older than 2 hours

            foreach ($tempFiles as $filePath) {
                $fileTime = $tempDisk->lastModified($filePath);
                
                if ($fileTime && Carbon::createFromTimestamp($fileTime)->lt($cutoffTime)) {
                    if ($tempDisk->delete($filePath)) {
                        $count++;
                    }
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to cleanup temp files', [
                'error' => $e->getMessage()
            ]);
        }

        return $count;
    }

    /**
     * Calculate approximate storage freed
     */
    protected function calculateStorageFreed(array $stats): float
    {
        // Rough estimate: average 2MB per report file
        $reportsFreed = $stats['completed_reports_cleaned'] + $stats['failed_reports_cleaned'];
        $filesFreed = $stats['files_deleted'] + $stats['temp_files_cleaned'];
        
        return round(($reportsFreed * 2) + ($filesFreed * 0.5), 2);
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('CleanupExpiredReports job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        return ['cleanup', 'maintenance', 'reports'];
    }
}
