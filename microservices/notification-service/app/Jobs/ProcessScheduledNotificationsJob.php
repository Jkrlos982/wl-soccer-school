<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessScheduledNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 1; // No retry for this job

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('notifications-high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting to process scheduled notifications');

        $processedCount = 0;
        $retryCount = 0;

        try {
            // Procesar notificaciones programadas
            $processedCount = $this->processScheduledNotifications();
            
            // Procesar reintentos
            $retryCount = $this->processRetryNotifications();

            Log::info('Finished processing notifications', [
                'scheduled_processed' => $processedCount,
                'retries_processed' => $retryCount,
                'total_processed' => $processedCount + $retryCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing scheduled notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process scheduled notifications that are ready to be sent
     */
    private function processScheduledNotifications(): int
    {
        $scheduledNotifications = Notification::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit(100)
            ->get();

        $processedCount = 0;

        foreach ($scheduledNotifications as $notification) {
            try {
                // Actualizar estado a queued
                $notification->update(['status' => 'queued']);
                $notification->logEvent('queued', 'Notification queued for sending');

                // Dispatch job con delay aleatorio para distribuir carga
                $delay = now()->addSeconds(rand(1, 30));
                
                SendNotificationJob::dispatch($notification)
                    ->delay($delay)
                    ->onQueue($this->getQueueForNotification($notification));

                $processedCount++;

                Log::debug('Scheduled notification queued', [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                    'delay_seconds' => $delay->diffInSeconds(now())
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to queue scheduled notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
                
                $notification->markAsFailed('Failed to queue: ' . $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * Process notifications that need to be retried
     */
    private function processRetryNotifications(): int
    {
        $retryNotifications = Notification::where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('failed_at', 'asc')
            ->limit(50)
            ->get();

        $retryCount = 0;

        foreach ($retryNotifications as $notification) {
            try {
                // Incrementar contador de reintentos
                $notification->increment('retry_count');
                
                // Calcular próximo reintento (exponential backoff)
                $nextRetryMinutes = pow(2, $notification->retry_count) * 5; // 5, 10, 20 minutes
                $notification->update([
                    'next_retry_at' => now()->addMinutes($nextRetryMinutes),
                    'status' => 'queued'
                ]);

                $notification->logEvent('retry', "Retrying failed notification (attempt {$notification->retry_count})");

                // Dispatch job con delay de 1 minuto
                SendNotificationJob::dispatch($notification)
                    ->delay(now()->addMinutes(1))
                    ->onQueue($this->getQueueForNotification($notification));

                $retryCount++;

                Log::debug('Failed notification queued for retry', [
                    'notification_id' => $notification->id,
                    'retry_count' => $notification->retry_count,
                    'next_retry_at' => $notification->next_retry_at
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to queue retry notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $retryCount;
    }

    /**
     * Get appropriate queue for notification based on priority
     */
    private function getQueueForNotification(Notification $notification): string
    {
        // Determinar prioridad basada en el tipo y metadata
        $priority = $notification->metadata['priority'] ?? 'normal';
        
        return match($priority) {
            'high', 'urgent' => 'notifications-high',
            'low' => 'notifications-low',
            default => 'notifications-default'
        };
    }

    /**
     * Clean up old processed notifications (optional)
     */
    private function cleanupOldNotifications(): int
    {
        // Eliminar notificaciones enviadas exitosamente más antiguas a 30 días
        $deletedCount = Notification::where('status', 'sent')
            ->where('sent_at', '<', now()->subDays(30))
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Cleaned up old notifications', [
                'deleted_count' => $deletedCount
            ]);
        }

        return $deletedCount;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'processor',
            'scheduled-notifications',
            'system'
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessScheduledNotificationsJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Opcional: Enviar alerta a administradores
        // $this->sendAdminAlert($exception);
    }
}