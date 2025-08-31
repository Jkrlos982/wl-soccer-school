<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'notes',
        'is_paid',
        'attachment_path',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'decimal:1',
        'approved_at' => 'datetime',
        'is_paid' => 'boolean',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Get the employee that owns the leave request.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the approver of the leave request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Check if leave request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if leave request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if leave request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if leave request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if leave is paid.
     */
    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    /**
     * Calculate duration in days.
     */
    public function calculateDuration(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Check if leave is current (ongoing).
     */
    public function isCurrent(): bool
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Check if leave is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->start_date > now()->toDateString();
    }

    /**
     * Check if leave is past.
     */
    public function isPast(): bool
    {
        return $this->end_date < now()->toDateString();
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        $days = $this->days_requested;
        return $days == 1 ? '1 day' : number_format($days, 1) . ' days';
    }

    /**
     * Get formatted date range.
     */
    public function getFormattedDateRangeAttribute(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('M d, Y');
        }
        
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Approve the leave request.
     */
    public function approve(int $approvedBy, string $notes = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Reject the leave request.
     */
    public function reject(int $rejectedBy, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'rejected_reason' => $reason,
        ]);
    }

    /**
     * Cancel the leave request.
     */
    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for cancelled requests.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for paid leave.
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope for unpaid leave.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope for specific leave type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('leave_type', $type);
    }

    /**
     * Scope for specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope for current leave.
     */
    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->where('status', 'approved');
    }

    /**
     * Scope for upcoming leave.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now()->toDateString())
                    ->where('status', 'approved');
    }
}
