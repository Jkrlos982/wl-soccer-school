<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'player_id', 'training_id', 'match_id', 'date', 'context',
        'minutes_played', 'goals_scored', 'assists', 'shots_on_target', 'shots_off_target',
        'passes_completed', 'passes_attempted', 'tackles_won', 'tackles_lost',
        'interceptions', 'fouls_committed', 'fouls_received', 'yellow_cards', 'red_cards',
        'saves', 'goals_conceded', 'clean_sheets', 'crosses_completed',
        'dribbles_successful', 'aerial_duels_won', 'notes', 'recorded_by'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Métodos auxiliares
    public function getPassAccuracyAttribute(): float
    {
        return $this->passes_attempted > 0 
            ? round(($this->passes_completed / $this->passes_attempted) * 100, 1)
            : 0;
    }

    public function getTackleSuccessRateAttribute(): float
    {
        $totalTackles = $this->tackles_won + $this->tackles_lost;
        return $totalTackles > 0 
            ? round(($this->tackles_won / $totalTackles) * 100, 1)
            : 0;
    }

    public function getShotAccuracyAttribute(): float
    {
        $totalShots = $this->shots_on_target + $this->shots_off_target;
        return $totalShots > 0 
            ? round(($this->shots_on_target / $totalShots) * 100, 1)
            : 0;
    }

    public function getTotalShotsAttribute(): int
    {
        return $this->shots_on_target + $this->shots_off_target;
    }

    public function getTotalTacklesAttribute(): int
    {
        return $this->tackles_won + $this->tackles_lost;
    }

    public function getTotalPassesAttribute(): int
    {
        return $this->passes_attempted;
    }

    public function getTotalCardsAttribute(): int
    {
        return $this->yellow_cards + $this->red_cards;
    }

    // Scopes
    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByContext($query, $context)
    {
        return $query->where('context', $context);
    }

    public function scopeInPeriod($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween('date', [$dateFrom, $dateTo]);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year);
    }

    public function scopeThisSeason($query)
    {
        return $query->where('date', '>=', now()->startOfYear());
    }

    public function scopeMatches($query)
    {
        return $query->where('context', 'match');
    }

    public function scopeTrainings($query)
    {
        return $query->where('context', 'training');
    }

    public function scopeFriendlies($query)
    {
        return $query->where('context', 'friendly');
    }

    public function scopeWithGoals($query)
    {
        return $query->where('goals_scored', '>', 0);
    }

    public function scopeWithAssists($query)
    {
        return $query->where('assists', '>', 0);
    }

    public function scopeWithCards($query)
    {
        return $query->where(function($q) {
            $q->where('yellow_cards', '>', 0)
              ->orWhere('red_cards', '>', 0);
        });
    }
}