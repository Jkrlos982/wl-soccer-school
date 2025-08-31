<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'payroll_concept_id',
        'amount',
        'base_amount',
        'rate',
        'quantity',
        'calculation_details',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'rate' => 'decimal:4',
        'quantity' => 'decimal:2',
        'calculation_details' => 'array',
    ];

    /**
     * Get the payroll that owns the detail.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the payroll concept that owns the detail.
     */
    public function payrollConcept(): BelongsTo
    {
        return $this->belongsTo(PayrollConcept::class);
    }

    /**
     * Check if detail is an earning.
     */
    public function isEarning(): bool
    {
        return $this->payrollConcept->isEarning();
    }

    /**
     * Check if detail is a deduction.
     */
    public function isDeduction(): bool
    {
        return $this->payrollConcept->isDeduction();
    }

    /**
     * Check if detail is a tax.
     */
    public function isTax(): bool
    {
        return $this->payrollConcept->isTax();
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Get calculation summary.
     */
    public function getCalculationSummaryAttribute(): string
    {
        $summary = [];
        
        if ($this->base_amount) {
            $summary[] = 'Base: ' . number_format($this->base_amount, 2);
        }
        
        if ($this->rate) {
            $summary[] = 'Rate: ' . number_format($this->rate, 4);
        }
        
        if ($this->quantity && $this->quantity != 1) {
            $summary[] = 'Qty: ' . number_format($this->quantity, 2);
        }
        
        return implode(' | ', $summary);
    }

    /**
     * Scope for earnings.
     */
    public function scopeEarnings($query)
    {
        return $query->whereHas('payrollConcept', function ($q) {
            $q->where('type', 'earning');
        });
    }

    /**
     * Scope for deductions.
     */
    public function scopeDeductions($query)
    {
        return $query->whereHas('payrollConcept', function ($q) {
            $q->where('type', 'deduction');
        });
    }

    /**
     * Scope for taxes.
     */
    public function scopeTaxes($query)
    {
        return $query->whereHas('payrollConcept', function ($q) {
            $q->where('type', 'tax');
        });
    }

    /**
     * Scope for specific concept.
     */
    public function scopeForConcept($query, $conceptId)
    {
        return $query->where('payroll_concept_id', $conceptId);
    }

    /**
     * Scope for specific payroll.
     */
    public function scopeForPayroll($query, $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }
}
