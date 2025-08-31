<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'check_in_time',
        'check_out_time',
        'break_start_time',
        'break_end_time',
        'worked_hours',
        'overtime_hours',
        'break_hours',
        'status',
        'shift_type',
        'notes',
        'is_overtime_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
        'worked_hours' => 'decimal:2',
        'is_overtime_approved' => 'boolean',
        'overtime_hours' => 'decimal:2',
        'break_hours' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Get the employee that owns the attendance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the approver of the attendance.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Check if attendance is present.
     */
    public function isPresent(): bool
    {
        return $this->status === 'present';
    }

    /**
     * Check if attendance is absent.
     */
    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    /**
     * Check if attendance is late.
     */
    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    /**
     * Check if attendance is on leave.
     */
    public function isOnLeave(): bool
    {
        return $this->status === 'leave';
    }

    /**
     * Check if attendance is approved.
     */
    public function isApproved(): bool
    {
        return !is_null($this->approved_at);
    }

    /**
     * Calculate total worked hours.
     */
    public function calculateTotalHours(): float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return 0;
        }

        $totalMinutes = $this->check_out_time->diffInMinutes($this->check_in_time);
        
        // Subtract break time if available
        if ($this->break_start_time && $this->break_end_time) {
            $breakMinutes = $this->break_end_time->diffInMinutes($this->break_start_time);
            $totalMinutes -= $breakMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate overtime hours.
     */
    public function calculateOvertimeHours(float $regularHoursLimit = 8): float
    {
        $totalHours = $this->calculateTotalHours();
        return max(0, $totalHours - $regularHoursLimit);
    }

    /**
     * Get formatted worked hours.
     */
    public function getFormattedWorkedHoursAttribute(): string
    {
        return number_format($this->worked_hours, 2) . ' hrs';
    }

    /**
     * Get formatted check-in time.
     */
    public function getFormattedCheckInAttribute(): string
    {
        return $this->check_in_time ? $this->check_in_time->format('H:i') : 'N/A';
    }

    /**
     * Get formatted check-out time.
     */
    public function getFormattedCheckOutAttribute(): string
    {
        return $this->check_out_time ? $this->check_out_time->format('H:i') : 'N/A';
    }

    /**
     * Mark attendance as approved.
     */
    public function approve(int $approvedBy): bool
    {
        return $this->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Scope for present attendance.
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Scope for absent attendance.
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    /**
     * Scope for late attendance.
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    /**
     * Scope for leave attendance.
     */
    public function scopeOnLeave($query)
    {
        return $query->where('status', 'leave');
    }

    /**
     * Scope for approved attendance.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Scope for pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->whereNull('approved_at');
    }

    /**
     * Scope for specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year);
    }

    /**
     * Scope for overtime attendance.
     */
    public function scopeWithOvertime($query)
    {
        return $query->where('overtime_hours', '>', 0);
    }
}
