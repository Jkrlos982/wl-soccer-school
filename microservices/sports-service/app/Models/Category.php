<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'name', 'description', 'min_age', 'max_age',
        'gender', 'max_players', 'training_days', 'training_start_time',
        'training_end_time', 'field_location', 'is_active', 'coach_id'
    ];
    
    protected $casts = [
        'training_days' => 'array',
        'training_start_time' => 'datetime:H:i',
        'training_end_time' => 'datetime:H:i',
        'is_active' => 'boolean'
    ];
    
    // Relaciones
    // public function school()
    // {
    //     return $this->belongsTo(School::class);
    // }
    
    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
    
    public function players()
    {
        return $this->hasMany(Player::class);
    }
    
    public function trainings()
    {
        return $this->hasMany(Training::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }
    
    // Métodos auxiliares
    public function canAcceptPlayer($birthDate)
    {
        $age = Carbon::parse($birthDate)->age;
        return $age >= $this->min_age && $age <= $this->max_age;
    }
    
    /**
     * Check if category has available spots for new players
     */
    public function hasAvailableSpots(): bool
    {
        // TODO: Implementar cuando la relación players esté disponible
        // return $this->players()->count() < $this->max_players;
        return true;
    }
    
    /**
     * Scope to filter by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}