<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'training_id',
        'player_id',
        'date',
        'status',
        'arrival_time',
        'notes',
        'recorded_by',
        'recorded_at'
    ];

    protected $casts = [
        'date' => 'date',
        'arrival_time' => 'datetime:H:i',
        'recorded_at' => 'datetime'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByTraining($query, $trainingId)
    {
        return $query->where('training_id', $trainingId);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('date', 'desc')->limit($limit);
    }

    // Métodos auxiliares
    public function isLate()
    {
        if (!$this->arrival_time || !$this->training) {
            return false;
        }
        
        return $this->arrival_time->gt($this->training->start_time);
    }
    
    public function getLateDuration()
    {
        if (!$this->isLate()) {
            return 0;
        }
        
        return $this->training->start_time->diffInMinutes($this->arrival_time);
    }
}