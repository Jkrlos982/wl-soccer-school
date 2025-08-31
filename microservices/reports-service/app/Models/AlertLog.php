<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'event_type',
        'severity',
        'message',
        'trigger_data',
        'notification_sent',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'metadata',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'metadata' => 'array',
        'notification_sent' => 'boolean',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    // Relationships
    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeTriggered($query)
    {
        return $query->where('event_type', 'triggered');
    }

    public function scopeResolved($query)
    {
        return $query->where('event_type', 'resolved');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('is_acknowledged', true);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeNotificationSent($query)
    {
        return $query->where('notification_sent', true);
    }

    public function scopeNotificationPending($query)
    {
        return $query->where('notification_sent', false);
    }

    // Methods
    public function markNotificationSent()
    {
        $this->update(['notification_sent' => true]);
    }

    public function acknowledge($userId, $message = null)
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);

        if ($message) {
            $metadata = $this->metadata ?? [];
            $metadata['acknowledgment_message'] = $message;
            $this->update(['metadata' => $metadata]);
        }
    }
}
