<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PaymentPlanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_plan_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'paid_date',
        'payment_id'
    ];

    protected $casts = [
        'payment_plan_id' => 'integer',
        'installment_number' => 'integer',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'payment_id' => 'integer'
    ];

    /**
     * Get the payment plan that this installment belongs to.
     */
    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    /**
     * Get the payment associated with this installment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if the installment is overdue.
     */
    public function getIsOverdueAttribute()
    {
        return $this->status === self::STATUS_PENDING && 
               $this->due_date < Carbon::today();
    }

    /**
     * Check if the installment is paid.
     */
    public function getIsPaidAttribute()
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Get the days until due date.
     */
    public function getDaysUntilDueAttribute()
    {
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    /**
     * Get the days overdue.
     */
    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return Carbon::today()->diffInDays($this->due_date);
    }

    /**
     * Scope a query to filter by payment plan.
     */
    public function scopeForPaymentPlan($query, $paymentPlanId)
    {
        return $query->where('payment_plan_id', $paymentPlanId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending installments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include paid installments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope a query to only include overdue installments.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
                    ->orWhere(function($q) {
                        $q->where('status', self::STATUS_PENDING)
                          ->where('due_date', '<', Carbon::today());
                    });
    }

    /**
     * Scope a query to filter by due date range.
     */
    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter installments due today.
     */
    public function scopeDueToday($query)
    {
        return $query->where('due_date', Carbon::today());
    }

    /**
     * Scope a query to filter installments due this week.
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to filter installments due this month.
     */
    public function scopeDueThisMonth($query)
    {
        return $query->whereBetween('due_date', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ]);
    }

    // Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_PAID => 'Pagado',
            self::STATUS_OVERDUE => 'Vencido',
            self::STATUS_CANCELLED => 'Cancelado'
        ];
    }

    /**
     * Mark the installment as paid.
     */
    public function markAsPaid($paymentId = null)
    {
        $this->status = self::STATUS_PAID;
        $this->paid_date = Carbon::now();
        if ($paymentId) {
            $this->payment_id = $paymentId;
        }
        $this->save();

        // Update payment plan status
        $this->paymentPlan->updateStatus();
    }

    /**
     * Mark the installment as overdue.
     */
    public function markAsOverdue()
    {
        if ($this->status === self::STATUS_PENDING && $this->due_date < Carbon::today()) {
            $this->status = self::STATUS_OVERDUE;
            $this->save();
        }
    }

    /**
     * Update overdue status for all pending installments.
     */
    public static function updateOverdueStatus()
    {
        self::where('status', self::STATUS_PENDING)
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => self::STATUS_OVERDUE]);
    }
}