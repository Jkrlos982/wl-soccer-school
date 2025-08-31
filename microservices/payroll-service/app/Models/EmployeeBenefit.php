<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmployeeBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_concept_id',
        'amount',
        'frequency',
        'start_date',
        'end_date',
        'status',
        'notes',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'assigned_at' => 'datetime',
    ];



    /**
     * Get the employee that owns the benefit.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll concept (benefit type).
     */
    public function payrollConcept(): BelongsTo
    {
        return $this->belongsTo(PayrollConcept::class);
    }

    /**
     * Get the creator of the benefit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * Get the updater of the benefit.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }

    /**
     * Check if benefit is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && 
               $this->effective_date <= now()->toDateString() &&
               ($this->end_date === null || $this->end_date >= now()->toDateString());
    }

    /**
     * Check if benefit is expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date < now()->toDateString();
    }

    /**
     * Check if benefit is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->effective_date > now()->toDateString();
    }

    /**
     * Calculate benefit amount based on employee's salary.
     */
    public function calculateAmount(float $baseSalary = null): float
    {
        if ($this->amount) {
            return $this->amount;
        }

        if ($this->rate && $baseSalary) {
            return $baseSalary * ($this->rate / 100);
        }

        // If no base salary provided, use employee's base salary
        if ($this->rate && $this->employee) {
            return $this->employee->base_salary * ($this->rate / 100);
        }

        return 0;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        if ($this->amount) {
            return number_format($this->amount, 2);
        }

        if ($this->rate) {
            return number_format($this->rate, 2) . '%';
        }

        return 'N/A';
    }

    /**
     * Get benefit status.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isUpcoming()) {
            return 'upcoming';
        }

        return 'active';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'upcoming' => 'info',
            'expired' => 'warning',
            'inactive' => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Activate the benefit.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the benefit.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * End the benefit.
     */
    public function end(string $endDate = null): bool
    {
        return $this->update([
            'end_date' => $endDate ?? now()->toDateString(),
            'is_active' => false,
        ]);
    }

    /**
     * Scope for active benefits.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('effective_date', '<=', now()->toDateString())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now()->toDateString());
                    });
    }

    /**
     * Scope for inactive benefits.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for expired benefits.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('end_date')
                    ->where('end_date', '<', now()->toDateString());
    }

    /**
     * Scope for upcoming benefits.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('effective_date', '>', now()->toDateString());
    }

    /**
     * Scope for specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for specific benefit type.
     */
    public function scopeForConcept($query, $conceptId)
    {
        return $query->where('payroll_concept_id', $conceptId);
    }

    /**
     * Scope for effective on date.
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $date);
                    });
    }

    /**
     * Scope for benefits with fixed amounts.
     */
    public function scopeFixedAmount($query)
    {
        return $query->whereNotNull('amount');
    }

    /**
     * Scope for benefits with percentage rates.
     */
    public function scopePercentageRate($query)
    {
        return $query->whereNotNull('rate');
    }

    /**
     * Scope for earnings benefits.
     */
    public function scopeEarnings($query)
    {
        return $query->whereHas('payrollConcept', function ($q) {
            $q->where('type', 'earning');
        });
    }

    /**
     * Scope for deduction benefits.
     */
    public function scopeDeductions($query)
    {
        return $query->whereHas('payrollConcept', function ($q) {
            $q->where('type', 'deduction');
        });
    }
}
