<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EventAttendee extends Model
{
    protected $fillable = [
        'event_id', 'attendee_type', 'attendee_id', 'attendee_name',
        'attendee_email', 'attendee_phone', 'status', 'role',
        'is_organizer', 'responded_at', 'checked_in_at', 'checked_out_at',
        'response_comment', 'send_reminders', 'last_reminder_sent'
    ];

    protected $casts = [
        'is_organizer' => 'boolean',
        'send_reminders' => 'boolean',
        'responded_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'last_reminder_sent' => 'datetime'
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

    public function isCheckedIn()
    {
        return !is_null($this->checked_in_at) && is_null($this->checked_out_at);
    }

    public function isCheckedOut()
    {
        return !is_null($this->checked_out_at);
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'accepted' => 'Aceptado',
            'declined' => 'Rechazado',
            'tentative' => 'Tentativo',
            default => 'Desconocido'
        };
    }

    public function getRoleLabel()
    {
        return match($this->role) {
            'organizer' => 'Organizador',
            'required' => 'Requerido',
            'optional' => 'Opcional',
            'resource' => 'Recurso',
            default => 'Participante'
        };
    }

    // Scopes
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOrganizers($query)
    {
        return $query->where('is_organizer', true);
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
