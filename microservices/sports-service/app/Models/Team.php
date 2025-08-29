<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'school_id',
        'category_id',
        'name',
        'description',
        'max_players',
        'season',
        'field_location',
        'is_active',
        'registration_open',
        'coach_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'registration_open' => 'boolean'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOpenForRegistration($query)
    {
        return $query->where('registration_open', true);
    }

    public function scopeBySeason($query, $season)
    {
        return $query->where('season', $season);
    }

    // Métodos auxiliares
    public function canAcceptMorePlayers(): bool
    {
        return $this->players()->count() < $this->max_players;
    }

    public function getAvailableSpotsAttribute(): int
    {
        return $this->max_players - $this->players()->count();
    }

    public function getCurrentPlayersCountAttribute(): int
    {
        return $this->players()->count();
    }
}
