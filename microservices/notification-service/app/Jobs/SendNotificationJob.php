<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\WhatsAppService;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    public $timeout = 120; // 2 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
        $this->onQueue('notifications-default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Verificar si la notificación ya fue procesada
        if (!in_array($this->notification->status, ['pending', 'failed'])) {
            Log::info('Notification already processed', [
                'notification_id' => $this->notification->id,
                'status' => $this->notification->status
            ]);
            return;
        }

        // Actualizar estado a enviando
        $this->notification->update(['status' => 'sending']);
        $this->notification->logEvent('sending', 'Starting to send notification');

        try {
            $result = $this->sendNotification();

            if ($result['success']) {
                $this->notification->markAsSent(
                    $result['message_id'] ?? null,
                    $result['response'] ?? null
                );
                
                Log::info('Notification sent successfully', [
                    'notification_id' => $this->notification->id,
                    'type' => $this->notification->type,
                    'message_id' => $result['message_id'] ?? null
                ]);
            } else {
                $this->notification->markAsFailed($result['error']);
                $this->fail(new Exception($result['error']));
            }
        } catch (Exception $e) {
            Log::error('Failed to send notification', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->notification->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Send notification based on type
     */
    private function sendNotification(): array
    {
        switch ($this->notification->type) {
            case 'whatsapp':
                return $this->sendWhatsApp();
            case 'email':
                return $this->sendEmail();
            case 'sms':
                return $this->sendSMS();
            case 'push':
                return $this->sendPush();
            default:
                throw new Exception('Unsupported notification type: ' . $this->notification->type);
        }
    }

    /**
     * Send WhatsApp notification
     */
    private function sendWhatsApp(): array
    {
        $whatsappService = app(WhatsAppService::class);

        try {
            // Verificar si tiene media
            if (!empty($this->notification->media_urls)) {
                $mediaUrl = $this->notification->media_urls[0];
                $mediaType = $this->detectMediaType($mediaUrl);

                $response = $whatsappService->sendMediaMessage(
                    $this->notification->recipient_phone,
                    $mediaType,
                    $mediaUrl,
                    $this->notification->content
                );
            } else {
                $response = $whatsappService->sendTextMessage(
                    $this->notification->recipient_phone,
                    $this->notification->content
                );
            }

            return [
                'success' => $response['success'] ?? false,
                'message_id' => $response['message_id'] ?? null,
                'response' => $response,
                'error' => $response['error'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'WhatsApp error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send Email notification
     */
    private function sendEmail(): array
    {
        $emailService = app(EmailService::class);

        try {
            $attachments = [];
            if (!empty($this->notification->media_urls)) {
                foreach ($this->notification->media_urls as $url) {
                    $attachments[] = ['path' => $url];
                }
            }

            $response = $emailService->sendTransactionalEmail(
                $this->notification->recipient_email,
                $this->notification->subject ?? 'Notification',
                $this->notification->content,
                $attachments
            );

            return [
                'success' => $response['success'] ?? true,
                'message_id' => $response['message_id'] ?? null,
                'response' => $response,
                'error' => $response['error'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Email error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS notification (placeholder)
     */
    private function sendSMS(): array
    {
        // TODO: Implementar SMSService cuando esté disponible
        Log::warning('SMS service not implemented yet', [
            'notification_id' => $this->notification->id
        ]);

        return [
            'success' => false,
            'error' => 'SMS service not implemented yet'
        ];
    }

    /**
     * Send Push notification (placeholder)
     */
    private function sendPush(): array
    {
        // TODO: Implementar PushNotificationService cuando esté disponible
        Log::warning('Push notification service not implemented yet', [
            'notification_id' => $this->notification->id
        ]);

        return [
            'success' => false,
            'error' => 'Push notification service not implemented yet'
        ];
    }

    /**
     * Detect media type from URL
     */
    private function detectMediaType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return match($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'pdf', 'doc', 'docx', 'xls', 'xlsx' => 'document',
            'mp4', 'avi', 'mov' => 'video',
            'mp3', 'wav', 'ogg' => 'audio',
            default => 'document'
        };
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationJob failed permanently', [
            'notification_id' => $this->notification->id,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        $this->notification->markAsFailed($exception->getMessage(), false);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'notification:' . $this->notification->id,
            'type:' . $this->notification->type,
            'school:' . ($this->notification->school_id ?? 'unknown')
        ];
    }
}