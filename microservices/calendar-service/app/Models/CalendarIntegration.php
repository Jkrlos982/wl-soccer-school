<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CalendarIntegration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'calendar_id', 'provider', 'external_calendar_id',
        'access_token', 'refresh_token', 'token_expires_at',
        'provider_settings', 'is_active', 'sync_enabled',
        'sync_direction', 'last_sync_at', 'sync_status'
    ];

    protected $casts = [
        'provider_settings' => 'array',
        'sync_status' => 'array',
        'is_active' => 'boolean',
        'sync_enabled' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime'
    ];

    protected $hidden = [
        'access_token', 'refresh_token'
    ];

    // Relaciones
    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    // Métodos auxiliares
    public function isTokenExpired()
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return Carbon::now()->isAfter($this->token_expires_at);
    }

    public function needsTokenRefresh()
    {
        if (!$this->token_expires_at) {
            return false;
        }

        // Renovar si expira en los próximos 5 minutos
        return Carbon::now()->addMinutes(5)->isAfter($this->token_expires_at);
    }

    public function getProviderLabel()
    {
        return match($this->provider) {
            'google' => 'Google Calendar',
            'outlook' => 'Microsoft Outlook',
            'apple' => 'Apple Calendar',
            'caldav' => 'CalDAV',
            default => ucfirst($this->provider)
        };
    }

    public function getSyncDirectionLabel()
    {
        return match($this->sync_direction) {
            'bidirectional' => 'Bidireccional',
            'import_only' => 'Solo Importar',
            'export_only' => 'Solo Exportar',
            default => 'Desconocido'
        };
    }

    public function getLastSyncStatus()
    {
        if (!$this->sync_status || !isset($this->sync_status['status'])) {
            return 'never';
        }

        return $this->sync_status['status'];
    }

    public function hasRecentSync($minutes = 60)
    {
        if (!$this->last_sync_at) {
            return false;
        }

        return Carbon::now()->diffInMinutes($this->last_sync_at) <= $minutes;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSyncEnabled($query)
    {
        return $query->where('sync_enabled', true);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNeedsSync($query)
    {
        return $query->where('sync_enabled', true)
                     ->where('is_active', true)
                     ->where(function($q) {
                         $q->whereNull('last_sync_at')
                           ->orWhere('last_sync_at', '<', Carbon::now()->subHour());
                     });
    }
}
