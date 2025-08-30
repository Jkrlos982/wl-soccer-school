<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'player_id',
        'medical_record_id',
        'medical_exam_id',
        'certificate_number',
        'certificate_type',
        'title',
        'description',
        'issue_date',
        'valid_from',
        'valid_until',
        'is_permanent',
        'issued_by',
        'doctor_license',
        'medical_center',
        'medical_findings',
        'recommendations',
        'restrictions',
        'status',
        'clearance_status',
        'revocation_reason',
        'revocation_date',
        'revoked_by',
        'pdf_path',
        'digital_signature',
        'attachments',
        'notify_expiration',
        'notification_days_before',
        'last_notification_sent',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'revocation_date' => 'date',
        'last_notification_sent' => 'datetime',
        'is_permanent' => 'boolean',
        'notify_expiration' => 'boolean',
        'restrictions' => 'array',
        'attachments' => 'array',
        'notification_days_before' => 'integer'
    ];

    /**
     * Get the medical record that owns the certificate.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Get the medical exam that owns the certificate.
     */
    public function medicalExam(): BelongsTo
    {
        return $this->belongsTo(MedicalExam::class);
    }

    /**
     * Get the user who created the certificate.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the certificate.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who revoked the certificate.
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Scope a query to only include issued certificates.
     */
    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    /**
     * Scope a query to only include valid certificates.
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'issued')
                    ->where(function ($q) {
                        $q->where('is_permanent', true)
                          ->orWhere('valid_until', '>=', now());
                    });
    }

    /**
     * Scope a query to only include expired certificates.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere(function ($q) {
                        $q->where('status', 'issued')
                          ->where('is_permanent', false)
                          ->where('valid_until', '<', now());
                    });
    }

    /**
     * Scope a query to only include certificates expiring soon.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'issued')
                    ->where('is_permanent', false)
                    ->where('valid_until', '<=', now()->addDays($days))
                    ->where('valid_until', '>=', now());
    }

    /**
     * Check if the certificate is valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'issued') {
            return false;
        }

        if ($this->is_permanent) {
            return true;
        }

        return $this->valid_until && $this->valid_until >= now();
    }

    /**
     * Check if the certificate is expired.
     */
    public function isExpired(): bool
    {
        if ($this->is_permanent) {
            return false;
        }

        return $this->valid_until && $this->valid_until < now();
    }

    /**
     * Check if the certificate is expiring soon.
     */
    public function isExpiringSoon($days = 30): bool
    {
        if ($this->is_permanent || !$this->valid_until) {
            return false;
        }

        return $this->valid_until <= now()->addDays($days) && $this->valid_until >= now();
    }

    /**
     * Check if notification should be sent.
     */
    public function shouldNotify(): bool
    {
        if (!$this->notify_expiration || $this->is_permanent) {
            return false;
        }

        $notificationDate = $this->valid_until->subDays($this->notification_days_before);
        
        return now() >= $notificationDate && 
               (!$this->last_notification_sent || 
                $this->last_notification_sent < $notificationDate);
    }
}