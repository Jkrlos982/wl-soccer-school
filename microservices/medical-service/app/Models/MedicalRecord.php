<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class MedicalRecord extends Model
{
    use HasFactory, LogsActivity;

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

    /**
     * Get the school relationship.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the player relationship.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get certificates relationship.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    /**
     * Generate a unique record number.
     */
    public function generateRecordNumber(): string
    {
        $year = now()->year;
        $schoolCode = str_pad($this->school_id, 3, '0', STR_PAD_LEFT);
        $sequence = str_pad(
            MedicalRecord::where('school_id', $this->school_id)
                ->whereYear('created_at', $year)
                ->count() + 1,
            4, '0', STR_PAD_LEFT
        );
        
        return "MR-{$year}-{$schoolCode}-{$sequence}";
    }

    /**
     * Check if medical exam is due.
     */
    public function isMedicalExamDue(): bool
    {
        if (!$this->next_medical_exam) {
            return true;
        }
        
        return $this->next_medical_exam->isPast();
    }

    /**
     * Check if has active injuries.
     */
    public function hasActiveInjuries(): bool
    {
        return $this->injuries()
            ->whereIn('status', ['active', 'recovering'])
            ->exists();
    }

    /**
     * Get latest medical exam.
     */
    public function getLatestMedicalExam()
    {
        return $this->medicalExams()
            ->where('status', 'completed')
            ->orderBy('exam_date', 'desc')
            ->first();
    }

    /**
     * Calculate BMI.
     */
    public function getBMI(): ?float
    {
        if (!$this->height || !$this->weight) {
            return null;
        }
        
        $heightInMeters = $this->height / 100;
        return round($this->weight / ($heightInMeters * $heightInMeters), 2);
    }

    /**
     * Get BMI category.
     */
    public function getBMICategory(): ?string
    {
        $bmi = $this->getBMI();
        
        if (!$bmi) return null;
        
        return match(true) {
            $bmi < 18.5 => 'Bajo peso',
            $bmi < 25 => 'Peso normal',
            $bmi < 30 => 'Sobrepeso',
            default => 'Obesidad'
        };
    }

    /**
     * Log access to medical record.
     */
    public function logAccess($userId, $action = 'view'): void
    {
        $accessLog = $this->access_log ?? [];
        $accessLog[] = [
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip()
        ];
        
        // Mantener solo los últimos 100 accesos
        if (count($accessLog) > 100) {
            $accessLog = array_slice($accessLog, -100);
        }
        
        $this->update(['access_log' => $accessLog]);
    }

    /**
     * Scope for records with valid clearance.
     */
    public function scopeWithValidClearance($query)
    {
        return $query->where('medical_clearance', true)
            ->where(function($q) {
                $q->whereNull('clearance_expiry_date')
                  ->orWhere('clearance_expiry_date', '>', now());
            });
    }

    /**
     * Scope for exam due records.
     */
    public function scopeExamDue($query)
    {
        return $query->where(function($q) {
            $q->whereNull('next_medical_exam')
              ->orWhere('next_medical_exam', '<=', now());
        });
    }

    /**
     * Scope for school records.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}