<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'template_id', 'type', 'category', 'recipient_type',
        'recipient_id', 'recipient_phone', 'recipient_email', 'recipient_name',
        'subject', 'content', 'variables', 'media_urls', 'status',
        'scheduled_at', 'provider', 'reference_type', 'reference_id',
        'metadata', 'created_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'media_urls' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'next_retry_at' => 'datetime'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Métodos auxiliares
    public function markAsSent($providerMessageId = null, $providerResponse = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $providerMessageId,
            'provider_response' => $providerResponse
        ]);

        $this->logEvent('sent', 'Notification sent successfully');
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);

        $this->logEvent('delivered', 'Notification delivered');
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);

        $this->logEvent('read', 'Notification read by recipient');
    }

    public function markAsFailed($errorMessage, $scheduleRetry = true): void
    {
        $retryCount = $this->retry_count + 1;
        $nextRetry = $scheduleRetry && $retryCount <= 3 
            ? now()->addMinutes(pow(2, $retryCount) * 5) // Exponential backoff
            : null;

        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'retry_count' => $retryCount,
            'next_retry_at' => $nextRetry
        ]);

        $this->logEvent('failed', $errorMessage);
    }

    public function logEvent($event, $description = null, $data = null): void
    {
        $this->logs()->create([
            'event' => $event,
            'description' => $description,
            'data' => $data,
            'occurred_at' => now()
        ]);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('scheduled_at', '<=', now())
                    ->whereIn('status', ['pending', 'queued']);
    }

    public function scopeForRetry($query)
    {
        return $query->where('status', 'failed')
                    ->where('next_retry_at', '<=', now())
                    ->where('retry_count', '<', 3);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByRecipient($query, $recipientType, $recipientId)
    {
        return $query->where('recipient_type', $recipientType)
                    ->where('recipient_id', $recipientId);
    }
}