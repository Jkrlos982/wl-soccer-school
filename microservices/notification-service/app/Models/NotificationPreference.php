<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_type',
        'user_id',
        'category',
        'whatsapp_enabled',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'schedule_preferences'
    ];

    protected $casts = [
        'whatsapp_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'schedule_preferences' => 'array'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Métodos auxiliares
    public function isEnabledForType(string $type): bool
    {
        return match($type) {
            'whatsapp' => $this->whatsapp_enabled,
            'email' => $this->email_enabled,
            'sms' => $this->sms_enabled,
            'push' => $this->push_enabled,
            default => false
        };
    }

    public function getEnabledChannels(): array
    {
        $channels = [];
        
        if ($this->whatsapp_enabled) $channels[] = 'whatsapp';
        if ($this->email_enabled) $channels[] = 'email';
        if ($this->sms_enabled) $channels[] = 'sms';
        if ($this->push_enabled) $channels[] = 'push';
        
        return $channels;
    }

    public function updatePreferences(array $preferences): void
    {
        $this->update([
            'whatsapp_enabled' => $preferences['whatsapp_enabled'] ?? $this->whatsapp_enabled,
            'email_enabled' => $preferences['email_enabled'] ?? $this->email_enabled,
            'sms_enabled' => $preferences['sms_enabled'] ?? $this->sms_enabled,
            'push_enabled' => $preferences['push_enabled'] ?? $this->push_enabled,
            'schedule_preferences' => $preferences['schedule_preferences'] ?? $this->schedule_preferences
        ]);
    }

    // Scopes
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByUser($query, $userType, $userId)
    {
        return $query->where('user_type', $userType)
                    ->where('user_id', $userId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeEnabledFor($query, $type)
    {
        $column = $type . '_enabled';
        return $query->where($column, true);
    }

    // Método estático para obtener o crear preferencias
    public static function getOrCreateForUser($schoolId, $userType, $userId, $category)
    {
        return static::firstOrCreate(
            [
                'school_id' => $schoolId,
                'user_type' => $userType,
                'user_id' => $userId,
                'category' => $category
            ],
            [
                'whatsapp_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => true
            ]
        );
    }
}