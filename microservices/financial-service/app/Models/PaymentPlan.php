<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'total_amount',
        'installments',
        'frequency',
        'start_date',
        'status',
        'description',
        'created_by'
    ];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'total_amount' => 'decimal:2',
        'installments' => 'integer',
        'start_date' => 'date',
        'created_by' => 'integer'
    ];

    /**
     * Get the user who created this payment plan.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the installments for this payment plan.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class);
    }

    /**
     * Get the paid installments for this payment plan.
     */
    public function paidInstallments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class)->where('status', 'paid');
    }

    /**
     * Get the pending installments for this payment plan.
     */
    public function pendingInstallments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class)->where('status', 'pending');
    }

    /**
     * Get the overdue installments for this payment plan.
     */
    public function overdueInstallments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class)->where('status', 'overdue');
    }

    /**
     * Calculate the total paid amount.
     */
    public function getPaidAmountAttribute()
    {
        return $this->paidInstallments->sum('amount');
    }

    /**
     * Calculate the remaining amount to be paid.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Calculate the completion percentage.
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->total_amount == 0) {
            return 0;
        }
        return round(($this->paid_amount / $this->total_amount) * 100, 2);
    }

    /**
     * Check if the payment plan is completed.
     */
    public function getIsCompletedAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Get the next due installment.
     */
    public function getNextDueInstallmentAttribute()
    {
        return $this->pendingInstallments()
                    ->orderBy('due_date')
                    ->first();
    }

    /**
     * Scope a query to filter by school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope a query to filter by student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include active payment plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include completed payment plans.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to filter by frequency.
     */
    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    // Constants
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_BIWEEKLY = 'biweekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_SEMESTER = 'semester';
    const FREQUENCY_ANNUAL = 'annual';

    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SUSPENDED = 'suspended';

    public static function getFrequencies()
    {
        return [
            self::FREQUENCY_WEEKLY => 'Semanal',
            self::FREQUENCY_BIWEEKLY => 'Quincenal',
            self::FREQUENCY_MONTHLY => 'Mensual',
            self::FREQUENCY_QUARTERLY => 'Trimestral',
            self::FREQUENCY_SEMESTER => 'Semestral',
            self::FREQUENCY_ANNUAL => 'Anual'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Activo',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_SUSPENDED => 'Suspendido'
        ];
    }

    /**
     * Generate installments for this payment plan.
     */
    public function generateInstallments()
    {
        // Delete existing installments
        $this->installments()->delete();

        $installmentAmount = $this->total_amount / $this->installments;
        $currentDate = $this->start_date;

        for ($i = 1; $i <= $this->installments; $i++) {
            PaymentPlanInstallment::create([
                'payment_plan_id' => $this->id,
                'installment_number' => $i,
                'amount' => $installmentAmount,
                'due_date' => $currentDate,
                'status' => PaymentPlanInstallment::STATUS_PENDING
            ]);

            // Calculate next due date based on frequency
            $currentDate = $this->calculateNextDueDate($currentDate);
        }
    }

    /**
     * Calculate the next due date based on frequency.
     */
    private function calculateNextDueDate($currentDate)
    {
        switch ($this->frequency) {
            case self::FREQUENCY_WEEKLY:
                return $currentDate->addWeek();
            case self::FREQUENCY_BIWEEKLY:
                return $currentDate->addWeeks(2);
            case self::FREQUENCY_MONTHLY:
                return $currentDate->addMonth();
            case self::FREQUENCY_QUARTERLY:
                return $currentDate->addMonths(3);
            case self::FREQUENCY_SEMESTER:
                return $currentDate->addMonths(6);
            case self::FREQUENCY_ANNUAL:
                return $currentDate->addYear();
            default:
                return $currentDate->addMonth();
        }
    }

    /**
     * Update the status based on installments.
     */
    public function updateStatus()
    {
        if ($this->is_completed) {
            $this->status = self::STATUS_COMPLETED;
        } elseif ($this->status !== self::STATUS_CANCELLED && $this->status !== self::STATUS_SUSPENDED) {
            $this->status = self::STATUS_ACTIVE;
        }
        
        $this->save();
    }
}