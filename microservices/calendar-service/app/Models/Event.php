<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Event extends Model
{
    use SoftDeletes;

    protected $table = 'calendar_events';

    protected $fillable = [
        'title', 'description', 'location', 'start_date', 'end_date',
        'timezone', 'is_all_day', 'status', 'visibility', 'calendar_id',
        'created_by', 'updated_by', 'external_id', 'external_provider',
        'attendees', 'recurrence_rule', 'recurrence_id', 'reminders', 'metadata'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_all_day' => 'boolean',
        'attendees' => 'array',
        'recurrence_rule' => 'array',
        'reminders' => 'array',
        'metadata' => 'array'
    ];

    // Relaciones
    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }

    public function eventAttendees()
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function eventResources()
    {
        return $this->hasMany(EventResource::class);
    }

    public function invitations()
    {
        return $this->hasMany(EventInvitation::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'event_resources')
                    ->withPivot('quantity', 'status', 'notes', 'cost')
                    ->withTimestamps();
    }

    // Métodos auxiliares
    public function isRecurring()
    {
        return !empty($this->recurrence_rule);
    }

    public function getDuration()
    {
        if ($this->is_all_day) {
            return 'All Day';
        }
        
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        
        return $start->diffForHumans($end, true);
    }

    public function isUpcoming()
    {
        return Carbon::parse($this->start_date)->isFuture();
    }

    public function isPast()
    {
        return Carbon::parse($this->end_date)->isPast();
    }

    public function isToday()
    {
        $today = Carbon::today();
        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $endDate = Carbon::parse($this->end_date)->endOfDay();
        
        return $today->between($startDate, $endDate);
    }

    public function canUserAccess($userId, $permission = 'view')
    {
        // Verificar si el usuario es el creador
        if ($this->created_by === $userId) {
            return true;
        }

        // Verificar permisos del calendario
        if ($this->calendar && $this->calendar->canUserAccess($userId, $permission)) {
            return true;
        }

        // Verificar si es un asistente
        if ($this->eventAttendees()->where('attendee_id', $userId)->exists()) {
            return $permission === 'view';
        }

        return false;
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeToday($query)
    {
        $today = Carbon::today();
        return $query->whereDate('start_date', '<=', $today)
                     ->whereDate('end_date', '>=', $today);
    }

    public function scopeForCalendar($query, $calendarId)
    {
        return $query->where('calendar_id', $calendarId);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $endDate);
              });
        });
    }
}
