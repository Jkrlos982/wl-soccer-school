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
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'arrival_time' => 'datetime:H:i:s'
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
}