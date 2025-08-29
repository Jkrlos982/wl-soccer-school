<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'event',
        'description',
        'data',
        'occurred_at'
    ];

    protected $casts = [
        'data' => 'array',
        'occurred_at' => 'datetime'
    ];

    // Relaciones
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    // Scopes
    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    public function scopeByNotification($query, $notificationId)
    {
        return $query->where('notification_id', $notificationId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }
}