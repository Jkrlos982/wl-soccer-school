<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'school_id',
        'player_id',
        'exam_type',
        'exam_code',
        'exam_date',
        'exam_time',
        'location',
        'doctor_name',
        'doctor_license_number',
        'doctor_specialty',
        'medical_center',
        'status',
        'result',
        'observations',
        'vital_signs',
        'physical_tests',
        'recommendations',
        'valid_from',
        'valid_until',
        'requires_followup',
        'followup_date',
        'followup_notes',
        'attachments',
        'certificate_path',
        'cost',
        'paid',
        'payment_date',
        'invoice_number',
        'scheduled_by',
        'completed_by'
    ];

    protected $casts = [
        'exam_date' => 'date',
        'exam_time' => 'datetime:H:i',
        'vital_signs' => 'array',
        'physical_tests' => 'array',
        'recommendations' => 'array',
        'attachments' => 'array',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'requires_followup' => 'boolean',
        'followup_date' => 'date',
        'cost' => 'decimal:2',
        'paid' => 'boolean',
        'payment_date' => 'date'
    ];

    /**
     * Get the medical record that owns the exam.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Get the user who scheduled the exam.
     */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /**
     * Get the user who completed the exam.
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the medical certificates for this exam.
     */
    public function medicalCertificates(): HasMany
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    /**
     * Scope a query to only include scheduled exams.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include completed exams.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include approved exams.
     */
    public function scopeApproved($query)
    {
        return $query->where('result', 'approved');
    }

    /**
     * Scope a query to only include exams requiring followup.
     */
    public function scopeRequiringFollowup($query)
    {
        return $query->where('requires_followup', true);
    }

    /**
     * Check if the exam is valid (not expired).
     */
    public function isValid(): bool
    {
        return $this->valid_until && $this->valid_until >= now();
    }

    /**
     * Check if followup is due.
     */
    public function isFollowupDue(): bool
    {
        return $this->requires_followup && $this->followup_date && $this->followup_date <= now();
    }

    /**
     * Check if the exam is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->exam_date < now();
    }
}