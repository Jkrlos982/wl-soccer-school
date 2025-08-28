<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountReceivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'concept_id',
        'amount',
        'due_date',
        'status',
        'description',
        'created_by'
    ];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'concept_id' => 'integer',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'created_by' => 'integer'
    ];

    /**
     * Get the financial concept for this account receivable.
     */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(FinancialConcept::class, 'concept_id');
    }

    /**
     * Get the user who created this account receivable.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the payments for this account receivable.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the confirmed payments for this account receivable.
     */
    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'confirmed');
    }

    /**
     * Calculate the remaining amount to be paid.
     */
    public function getRemainingAmountAttribute()
    {
        $paidAmount = $this->confirmedPayments->sum('amount');
        return $this->amount - $paidAmount;
    }

    /**
     * Calculate the paid amount.
     */
    public function getPaidAmountAttribute()
    {
        return $this->confirmedPayments->sum('amount');
    }

    /**
     * Check if the account receivable is fully paid.
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Check if the account receivable is overdue.
     */
    public function getIsOverdueAttribute()
    {
        return $this->due_date < now() && !$this->is_fully_paid;
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
     * Scope a query to only include pending accounts receivable.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include overdue accounts receivable.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                    ->orWhere(function($q) {
                        $q->where('due_date', '<', now())
                          ->whereIn('status', ['pending', 'partial']);
                    });
    }

    /**
     * Scope a query to only include paid accounts receivable.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to filter by due date range.
     */
    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    // Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_PARTIAL => 'Pago Parcial',
            self::STATUS_PAID => 'Pagado',
            self::STATUS_OVERDUE => 'Vencido',
            self::STATUS_CANCELLED => 'Cancelado'
        ];
    }

    /**
     * Update the status based on payments.
     */
    public function updateStatus()
    {
        $paidAmount = $this->paid_amount;
        
        if ($paidAmount >= $this->amount) {
            $this->status = self::STATUS_PAID;
        } elseif ($paidAmount > 0) {
            $this->status = self::STATUS_PARTIAL;
        } elseif ($this->due_date < now()) {
            $this->status = self::STATUS_OVERDUE;
        } else {
            $this->status = self::STATUS_PENDING;
        }
        
        $this->save();
    }
}