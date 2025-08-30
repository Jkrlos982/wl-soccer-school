<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'player_id',
        'record_number',
        'blood_type',
        'height',
        'weight',
        'allergies',
        'chronic_conditions',
        'medications',
        'emergency_contacts',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiry_date',
        'primary_doctor_name',
        'primary_doctor_phone',
        'primary_doctor_email',
        'is_active',
        'status',
        'notes',
        'last_medical_exam',
        'next_medical_exam',
        'medical_clearance',
        'clearance_expiry_date',
        'access_log',
        'consent_given',
        'consent_date',
        'consent_given_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'allergies' => 'array',
        'chronic_conditions' => 'array',
        'medications' => 'array',
        'emergency_contacts' => 'array',
        'access_log' => 'array',
        'is_active' => 'boolean',
        'medical_clearance' => 'boolean',
        'consent_given' => 'boolean',
        'insurance_expiry_date' => 'date',
        'last_medical_exam' => 'date',
        'next_medical_exam' => 'date',
        'clearance_expiry_date' => 'date',
        'consent_date' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2'
    ];

    /**
     * Get the medical exams for the medical record.
     */
    public function medicalExams(): HasMany
    {
        return $this->hasMany(MedicalExam::class);
    }

    /**
     * Get the injuries for the medical record.
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(Injury::class);
    }

    /**
     * Get the medical certificates for the medical record.
     */
    public function medicalCertificates(): HasMany
    {
        return $this->hasMany(MedicalCertificate::class);
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
     * Get the user who gave consent.
     */
    public function consentGiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consent_given_by');
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include records with medical clearance.
     */
    public function scopeWithClearance($query)
    {
        return $query->where('medical_clearance', true)
                    ->where('clearance_expiry_date', '>', now());
    }

    /**
     * Check if medical clearance is expired.
     */
    public function isClearanceExpired(): bool
    {
        return $this->clearance_expiry_date && $this->clearance_expiry_date < now();
    }

    /**
     * Check if next medical exam is due.
     */
    public function isExamDue(): bool
    {
        return $this->next_medical_exam && $this->next_medical_exam <= now();
    }
}