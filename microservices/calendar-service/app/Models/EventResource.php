<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventResource extends Model
{
    protected $fillable = [
        'event_id', 'resource_id', 'quantity', 'status', 'notes', 'cost'
    ];

    protected $casts = [
        'cost' => 'decimal:2'
    ];

    // Relaciones
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    // Métodos auxiliares
    public function getStatusLabel()
    {
        return match($this->status) {
            'requested' => 'Solicitado',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            default => 'Desconocido'
        };
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isPending()
    {
        return $this->status === 'requested';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function getTotalCost()
    {
        if ($this->cost) {
            return $this->cost * $this->quantity;
        }

        if ($this->resource && $this->resource->hourly_rate && $this->event) {
            $hours = $this->event->start_date->diffInHours($this->event->end_date);
            return $this->resource->calculateCost($hours, $this->quantity);
        }

        return 0;
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'requested');
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeForResource($query, $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }
}
