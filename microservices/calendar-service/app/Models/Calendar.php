<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calendar extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'color', 'timezone',
        'user_id', 'organization_id', 'is_public', 'is_default',
        'settings', 'status'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_public' => 'boolean',
        'is_default' => 'boolean'
    ];

    // Relaciones
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function integrations()
    {
        return $this->hasMany(CalendarIntegration::class);
    }

    // Métodos auxiliares
    public function getEventsForPeriod($startDate, $endDate)
    {
        return $this->events()
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->orderBy('start_date')
            ->get();
    }

    public function canUserAccess($userId, $permission = 'view')
    {
        if ($this->is_public && $permission === 'view') {
            return true;
        }

        if ($this->user_id === $userId) {
            return true;
        }

        // Aquí se pueden agregar más lógicas de permisos
        return false;
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}
