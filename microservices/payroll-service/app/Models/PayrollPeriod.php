<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'pay_date',
        'period_type',
        'status',
        'year',
        'month',
        'period_number',
        'total_gross',
        'total_deductions',
        'total_net',
        'approved_at',
        'approved_by',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get payrolls for this period.
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get payroll reports for this period.
     */
    public function payrollReports(): HasMany
    {
        return $this->hasMany(PayrollReport::class);
    }

    /**
     * Check if period is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if period is closed.
     */
    public function isClosed(): bool
    {
        return $this->is_closed;
    }

    /**
     * Check if period is current.
     */
    public function isCurrent(): bool
    {
        $today = Carbon::today();
        return $today->between($this->start_date, $this->end_date);
    }

    /**
     * Get period duration in days.
     */
    public function getDurationAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get formatted period name.
     */
    public function getFormattedPeriodAttribute(): string
    {
        return $this->start_date->format('M d') . ' - ' . $this->end_date->format('M d, Y');
    }

    /**
     * Get total payroll amount for this period.
     */
    public function getTotalPayrollAmount(): float
    {
        return $this->payrolls()->sum('net_salary');
    }

    /**
     * Get total employees count for this period.
     */
    public function getTotalEmployeesCount(): int
    {
        return $this->payrolls()->count();
    }

    /**
     * Get processed payrolls count.
     */
    public function getProcessedPayrollsCount(): int
    {
        return $this->payrolls()->where('status', 'processed')->count();
    }

    /**
     * Get pending payrolls count.
     */
    public function getPendingPayrollsCount(): int
    {
        return $this->payrolls()->where('status', 'draft')->count();
    }

    /**
     * Check if all payrolls are processed.
     */
    public function allPayrollsProcessed(): bool
    {
        return $this->getPendingPayrollsCount() === 0;
    }

    /**
     * Close the payroll period.
     */
    public function close(int $userId = null): bool
    {
        if ($this->is_closed) {
            return false;
        }

        $this->update([
            'is_closed' => true,
            'closed_at' => now(),
            'closed_by' => $userId,
            'status' => 'closed',
        ]);

        return true;
    }

    /**
     * Reopen the payroll period.
     */
    public function reopen(): bool
    {
        if (!$this->is_closed) {
            return false;
        }

        $this->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
            'status' => 'active',
        ]);

        return true;
    }

    /**
     * Scope for active periods.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for closed periods.
     */
    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    /**
     * Scope for current period.
     */
    public function scopeCurrent($query)
    {
        $today = Carbon::today();
        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }
}
