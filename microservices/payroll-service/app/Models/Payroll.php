<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'payroll_number',
        'base_salary',
        'gross_salary',
        'total_earnings',
        'total_deductions',
        'total_taxes',
        'net_salary',
        'worked_days',
        'worked_hours',
        'regular_hours',
        'overtime_hours',
        'overtime_amount',
        'status',
        'notes',
        'calculated_at',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_taxes' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'worked_days' => 'decimal:2',
        'worked_hours' => 'decimal:2',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the employee that owns the payroll.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll period that owns the payroll.
     */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Get payroll details for this payroll.
     */
    public function payrollDetails(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    /**
     * Alias for payrollDetails relationship.
     */
    public function details(): HasMany
    {
        return $this->payrollDetails();
    }

    /**
     * Get earnings details.
     */
    public function earnings(): HasMany
    {
        return $this->payrollDetails()
            ->whereHas('payrollConcept', function ($query) {
                $query->where('type', 'earning');
            });
    }

    /**
     * Get deductions details.
     */
    public function deductions(): HasMany
    {
        return $this->payrollDetails()
            ->whereHas('payrollConcept', function ($query) {
                $query->where('type', 'deduction');
            });
    }

    /**
     * Get taxes details.
     */
    public function taxes(): HasMany
    {
        return $this->payrollDetails()
            ->whereHas('payrollConcept', function ($query) {
                $query->where('type', 'tax');
            });
    }

    /**
     * Get concepts for this payroll.
     */
    public function concepts()
    {
        return $this->details()->with('payrollConcept');
    }

    /**
     * Check if payroll is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if payroll is calculated.
     */
    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    /**
     * Check if payroll is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if payroll is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if payroll is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Calculate gross salary.
     */
    public function calculateGrossSalary(): float
    {
        return $this->base_salary + $this->overtime_amount + $this->earnings()->sum('amount');
    }

    /**
     * Calculate net salary.
     */
    public function calculateNetSalary(): float
    {
        return $this->gross_salary - $this->total_deductions - $this->total_taxes;
    }

    /**
     * Get tax rate percentage.
     */
    public function getTaxRateAttribute(): float
    {
        return $this->gross_salary > 0 ? ($this->total_taxes / $this->gross_salary) * 100 : 0;
    }

    /**
     * Get deduction rate percentage.
     */
    public function getDeductionRateAttribute(): float
    {
        return $this->gross_salary > 0 ? ($this->total_deductions / $this->gross_salary) * 100 : 0;
    }

    /**
     * Get net rate percentage.
     */
    public function getNetRateAttribute(): float
    {
        return $this->gross_salary > 0 ? ($this->net_salary / $this->gross_salary) * 100 : 0;
    }

    /**
     * Mark payroll as calculated.
     */
    public function markAsCalculated(): bool
    {
        return $this->update([
            'status' => 'calculated',
            'calculated_at' => now(),
        ]);
    }

    /**
     * Mark payroll as approved.
     */
    public function markAsApproved(): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark payroll as processed.
     */
    public function markAsProcessed(): bool
    {
        return $this->update([
            'status' => 'processed',
        ]);
    }

    /**
     * Mark payroll as paid.
     */
    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Scope for pending payrolls.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for calculated payrolls.
     */
    public function scopeCalculated($query)
    {
        return $query->where('status', 'calculated');
    }

    /**
     * Scope for approved payrolls.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for processed payrolls.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for paid payrolls.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for current period.
     */
    public function scopeForPeriod($query, $periodId)
    {
        return $query->where('payroll_period_id', $periodId);
    }

    /**
     * Scope for employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
