<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EventInvitation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id', 'email', 'name', 'user_id', 'status', 'role',
        'invitation_token', 'invited_at', 'responded_at',
        'response_message', 'notification_preferences'
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
        'notification_preferences' => 'array'
    ];

    // Relaciones
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Métodos auxiliares
    public function hasResponded()
    {
        return !is_null($this->responded_at);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isDeclined()
    {
        return $this->status === 'declined';
    }

    public function generateToken()
    {
        $this->invitation_token = Str::random(64);
        return $this->invitation_token;
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'accepted' => 'Aceptado',
            'declined' => 'Rechazado',
            'expired' => 'Expirado',
            default => 'Desconocido'
        };
    }

    public function getRoleLabel()
    {
        return match($this->role) {
            'organizer' => 'Organizador',
            'required' => 'Requerido',
            'optional' => 'Opcional',
            'viewer' => 'Observador',
            default => 'Invitado'
        };
    }

    public function isExpired()
    {
        if (!$this->event) {
            return false;
        }

        // La invitación expira si el evento ya pasó
        return Carbon::now()->isAfter($this->event->start_date);
    }

    public function canRespond()
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function getResponseUrl()
    {
        return route('invitations.respond', ['token' => $this->invitation_token]);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByToken($query, $token)
    {
        return $query->where('invitation_token', $token);
    }

    public function scopeNotExpired($query)
    {
        return $query->whereHas('event', function($q) {
            $q->where('start_date', '>', Carbon::now());
        });
    }

    // Boot method para generar token automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->invitation_token)) {
                $invitation->generateToken();
            }
            if (empty($invitation->invited_at)) {
                $invitation->invited_at = Carbon::now();
            }
        });
    }
}
