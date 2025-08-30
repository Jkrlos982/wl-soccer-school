<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Injury extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'school_id',
        'player_id',
        'injury_code',
        'injury_type',
        'body_part',
        'severity',
        'injury_datetime',
        'injury_time',
        'location',
        'activity_during_injury',
        'circumstances',
        'weather_conditions',
        'surface_type',
        'equipment_involved',
        'witnesses',
        'immediate_action_taken',
        'first_aid_provided',
        'first_aid_by',
        'diagnosis',
        'diagnosed_by',
        'diagnosis_date',
        'treatment_plan',
        'medications_prescribed',
        'therapy_required',
        'surgery_required',
        'surgery_date',
        'surgeon_name',
        'hospital',
        'estimated_recovery_days',
        'actual_recovery_time',
        'return_to_play_date',
        'return_to_play_clearance',
        'clearance_given_by',
        'prevention_measures',
        'impact_on_performance',
        'psychological_impact',
        'status',
        'notes',
        'description',
        'injury_context',
        'attachments',
        'insurance_claim_number',
        'insurance_approved',
        'cost',
        'reported_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'injury_datetime' => 'datetime',
        'injury_time' => 'datetime:H:i',
        'diagnosis_date' => 'date',
        'surgery_date' => 'date',
        'return_to_play_date' => 'date',
        'witnesses' => 'array',
        'medications_prescribed' => 'array',
        'prevention_measures' => 'array',
        'attachments' => 'array',
        'therapy_required' => 'boolean',
        'surgery_required' => 'boolean',
        'return_to_play_clearance' => 'boolean',
        'insurance_approved' => 'boolean',
        'cost' => 'decimal:2'
    ];

    /**
     * Get the medical record that owns the injury.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Get the injury followups.
     */
    public function followups(): HasMany
    {
        return $this->hasMany(InjuryFollowup::class);
    }

    /**
     * Get the user who reported the injury.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active injuries.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include recovered injuries.
     */
    public function scopeRecovered($query)
    {
        return $query->where('status', 'recovered');
    }

    /**
     * Scope a query to only include severe injuries.
     */
    public function scopeSevere($query)
    {
        return $query->whereIn('severity', ['severe', 'critical']);
    }

    /**
     * Check if the injury is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the player has return-to-play clearance.
     */
    public function hasReturnToPlayClearance(): bool
    {
        return $this->return_to_play_clearance === true;
    }

    /**
     * Check if recovery time has been exceeded.
     */
    public function isRecoveryOverdue(): bool
    {
        if (!$this->estimated_recovery_days || $this->status === 'recovered') {
            return false;
        }

        $estimatedRecoveryDate = $this->injury_datetime->addDays($this->estimated_recovery_days);
        return now() > $estimatedRecoveryDate;
    }
}