<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Exception;

class NotificationService
{
    protected array $config;
    protected array $rateLimits;

    public function __construct()
    {
        $this->config = config('reminders.notifications', []);
        $this->rateLimits = config('reminders.notifications.rate_limiting', []);
    }

    /**
     * Send notification using the specified channel
     */
    public function send(string $channel, array $recipient, string $templateCode, array $variables = [], ?int $schoolId = null): bool
    {
        try {
            // Check rate limiting
            if (!$this->checkRateLimit($recipient, $channel)) {
                Log::warning('Rate limit exceeded for notification', [
                    'channel' => $channel,
                    'recipient' => $recipient,
                    'template' => $templateCode
                ]);
                return false;
            }

            // Get template
            $template = $this->getTemplate($templateCode, $schoolId);
            if (!$template) {
                Log::error('Template not found', ['code' => $templateCode, 'school_id' => $schoolId]);
                return false;
            }

            // Render content
            $content = $this->renderTemplate($template, $variables);
            
            // Send notification
            $result = $this->sendByChannel($channel, $recipient, $content, $template);
            
            // Log the notification
            $this->logNotification($channel, $recipient, $templateCode, $content, $result, $schoolId);
            
            // Update rate limiting
            $this->updateRateLimit($recipient, $channel);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Failed to send notification', [
                'channel' => $channel,
                'recipient' => $recipient,
                'template' => $templateCode,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send notification by specific channel
     */
    protected function sendByChannel(string $channel, array $recipient, array $content, NotificationTemplate $template): bool
    {
        switch ($channel) {
            case 'whatsapp':
                return $this->sendWhatsApp($recipient, $content);
            case 'email':
                return $this->sendEmail($recipient, $content, $template);
            case 'sms':
                return $this->sendSMS($recipient, $content);
            case 'push':
                return $this->sendPushNotification($recipient, $content);
            default:
                Log::error('Unknown notification channel', ['channel' => $channel]);
                return false;
        }
    }

    /**
     * Send WhatsApp notification
     */
    protected function sendWhatsApp(array $recipient, array $content): bool
    {
        $config = config('reminders.channels.whatsapp');
        
        if (!$config['enabled'] || !isset($recipient['phone'])) {
            return false;
        }

        try {
            $response = Http::timeout($config['timeout'])
                ->withToken($config['token'])
                ->post($config['api_url'], [
                    'phone' => $recipient['phone'],
                    'message' => $content['body'],
                ]);

            return $response->successful();
            
        } catch (Exception $e) {
            Log::error('WhatsApp notification failed', [
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmail(array $recipient, array $content, NotificationTemplate $template): bool
    {
        $config = config('reminders.channels.email');
        
        if (!$config['enabled'] || !isset($recipient['email'])) {
            return false;
        }

        try {
            Mail::send([], [], function ($message) use ($recipient, $content, $config) {
                $message->to($recipient['email'], $recipient['name'] ?? '')
                       ->subject($content['subject'])
                       ->html($content['body'])
                       ->from($config['from_address'], $config['from_name']);
            });

            return true;
            
        } catch (Exception $e) {
            Log::error('Email notification failed', [
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send SMS notification
     */
    protected function sendSMS(array $recipient, array $content): bool
    {
        $config = config('reminders.channels.sms');
        
        if (!$config['enabled'] || !isset($recipient['phone'])) {
            return false;
        }

        // Implementation depends on SMS provider (Twilio, etc.)
        Log::info('SMS notification would be sent', [
            'recipient' => $recipient,
            'content' => $content
        ]);
        
        return true; // Placeholder
    }

    /**
     * Send push notification
     */
    protected function sendPushNotification(array $recipient, array $content): bool
    {
        $config = config('reminders.channels.push');
        
        if (!$config['enabled'] || !isset($recipient['device_token'])) {
            return false;
        }

        // Implementation depends on push service (Firebase, etc.)
        Log::info('Push notification would be sent', [
            'recipient' => $recipient,
            'content' => $content
        ]);
        
        return true; // Placeholder
    }

    /**
     * Get notification template
     */
    protected function getTemplate(string $code, ?int $schoolId = null): ?NotificationTemplate
    {
        $cacheKey = "notification_template:{$code}:{$schoolId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($code, $schoolId) {
            $query = NotificationTemplate::where('code', $code)
                                       ->where('is_active', true);
            
            if ($schoolId) {
                $query->where(function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)
                      ->orWhereNull('school_id');
                })->orderBy('school_id', 'desc'); // Prefer school-specific templates
            } else {
                $query->whereNull('school_id');
            }
            
            return $query->first();
        });
    }

    /**
     * Render template with variables
     */
    protected function renderTemplate(NotificationTemplate $template, array $variables): array
    {
        $subject = $this->replaceVariables($template->subject ?? '', $variables);
        $body = $this->replaceVariables($template->body, $variables);
        
        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Replace variables in template content
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        $pattern = config('reminders.templates.variable_pattern', '/{{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*}}/');
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = $matches[1];
            return $variables[$variable] ?? $matches[0]; // Keep original if variable not found
        }, $content);
    }

    /**
     * Check rate limiting
     */
    protected function checkRateLimit(array $recipient, string $channel): bool
    {
        if (!$this->rateLimits['enabled']) {
            return true;
        }

        $userId = $recipient['id'] ?? 'anonymous';
        $hourKey = "rate_limit:hour:{$userId}:{$channel}";
        $dayKey = "rate_limit:day:{$userId}:{$channel}";
        
        $hourCount = Cache::get($hourKey, 0);
        $dayCount = Cache::get($dayKey, 0);
        
        return $hourCount < $this->rateLimits['max_per_user_per_hour'] &&
               $dayCount < $this->rateLimits['max_per_user_per_day'];
    }

    /**
     * Update rate limiting counters
     */
    protected function updateRateLimit(array $recipient, string $channel): void
    {
        if (!$this->rateLimits['enabled']) {
            return;
        }

        $userId = $recipient['id'] ?? 'anonymous';
        $hourKey = "rate_limit:hour:{$userId}:{$channel}";
        $dayKey = "rate_limit:day:{$userId}:{$channel}";
        
        Cache::increment($hourKey, 1);
        Cache::expire($hourKey, 3600); // 1 hour
        
        Cache::increment($dayKey, 1);
        Cache::expire($dayKey, 86400); // 24 hours
    }

    /**
     * Log notification attempt
     */
    protected function logNotification(string $channel, array $recipient, string $templateCode, array $content, bool $success, ?int $schoolId = null): void
    {
        try {
            NotificationLog::create([
                'channel' => $channel,
                'recipient_type' => 'user',
                'recipient_id' => $recipient['id'] ?? null,
                'recipient_data' => json_encode($recipient),
                'template_code' => $templateCode,
                'subject' => $content['subject'] ?? null,
                'body' => $content['body'],
                'status' => $success ? 'sent' : 'failed',
                'school_id' => $schoolId,
                'sent_at' => $success ? now() : null,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log notification', [
                'error' => $e->getMessage(),
                'channel' => $channel,
                'template' => $templateCode
            ]);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStats(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_sent' => NotificationLog::where('created_at', '>=', $startDate)
                                         ->where('status', 'sent')
                                         ->count(),
            'total_failed' => NotificationLog::where('created_at', '>=', $startDate)
                                           ->where('status', 'failed')
                                           ->count(),
            'by_channel' => NotificationLog::where('created_at', '>=', $startDate)
                                         ->groupBy('channel')
                                         ->selectRaw('channel, COUNT(*) as count')
                                         ->pluck('count', 'channel')
                                         ->toArray(),
            'success_rate' => $this->calculateSuccessRate($startDate),
        ];
    }

    /**
     * Calculate success rate
     */
    protected function calculateSuccessRate(\Carbon\Carbon $startDate): float
    {
        $total = NotificationLog::where('created_at', '>=', $startDate)->count();
        
        if ($total === 0) {
            return 100.0;
        }
        
        $successful = NotificationLog::where('created_at', '>=', $startDate)
                                   ->where('status', 'sent')
                                   ->count();
        
        return round(($successful / $total) * 100, 2);
    }
}