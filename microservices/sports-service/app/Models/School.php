<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'address',
        'phone',
        'email',
        'website',
        'logo_url',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    /**
     * Get all categories for this school
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get all teams for this school
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get all players for this school
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Scope to get only active schools
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
