<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = [
        'school_id', 'name', 'type', 'description', 'location',
        'capacity', 'availability_schedule', 'requires_approval',
        'hourly_rate', 'is_active', 'status', 'equipment_included',
        'booking_rules', 'metadata', 'created_by'
    ];

    protected $casts = [
        'availability_schedule' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'equipment_included' => 'array',
        'booking_rules' => 'array',
        'metadata' => 'array',
        'hourly_rate' => 'decimal:2'
    ];

    // Relaciones
    public function eventResources()
    {
        return $this->hasMany(EventResource::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_resources')
                    ->withPivot('quantity', 'status', 'notes', 'cost')
                    ->withTimestamps();
    }

    // Métodos auxiliares
    public function isAvailable($startDate, $endDate)
    {
        if (!$this->is_active) {
            return false;
        }

        // Verificar si hay conflictos con eventos existentes
        $conflictingEvents = $this->events()
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->wherePivot('status', 'confirmed')
            ->count();

        return $conflictingEvents === 0;
    }

    public function getTypeLabel()
    {
        return match($this->type) {
            'room' => 'Sala',
            'equipment' => 'Equipo',
            'vehicle' => 'Vehículo',
            'facility' => 'Instalación',
            'person' => 'Persona',
            default => 'Recurso'
        };
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            'available' => 'Disponible',
            'maintenance' => 'Mantenimiento',
            'reserved' => 'Reservado',
            'out_of_order' => 'Fuera de Servicio',
            default => 'Desconocido'
        };
    }

    public function calculateCost($hours, $quantity = 1)
    {
        if (!$this->hourly_rate) {
            return 0;
        }

        return $this->hourly_rate * $hours * $quantity;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }
}
