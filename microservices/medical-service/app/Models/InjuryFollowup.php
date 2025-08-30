<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InjuryFollowup extends Model
{
    use HasFactory;

    protected $fillable = [
        'injury_id',
        'school_id',
        'player_id',
        'followup_date',
        'followup_time',
        'followup_type',
        'conducted_by',
        'location',
        'status',
        'pain_level',
        'mobility_assessment',
        'strength_assessment',
        'functional_tests',
        'progress_evaluation',
        'observations',
        'recommendations',
        'next_followup_date',
        'treatment_modifications',
        'medication_changes',
        'therapy_progress',
        'return_to_activity_level',
        'clearance_status',
        'attachments',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'followup_date' => 'date',
        'followup_time' => 'datetime:H:i',
        'next_followup_date' => 'date',
        'functional_tests' => 'array',
        'recommendations' => 'array',
        'treatment_modifications' => 'array',
        'medication_changes' => 'array',
        'attachments' => 'array',
        'pain_level' => 'integer'
    ];

    /**
     * Get the injury that owns the followup.
     */
    public function injury(): BelongsTo
    {
        return $this->belongsTo(Injury::class);
    }

    /**
     * Get the user who conducted the followup.
     */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
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
     * Scope a query to only include completed followups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include scheduled followups.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include followups with clearance.
     */
    public function scopeWithClearance($query)
    {
        return $query->where('clearance_status', 'cleared');
    }

    /**
     * Check if the followup shows improvement.
     */
    public function showsImprovement(): bool
    {
        return $this->progress_evaluation === 'improved' || $this->progress_evaluation === 'significantly_improved';
    }

    /**
     * Check if next followup is due.
     */
    public function isNextFollowupDue(): bool
    {
        return $this->next_followup_date && $this->next_followup_date <= now();
    }

    /**
     * Check if the followup is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->followup_date < now();
    }
}