<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'birth_date',
        'gender',
        'identification_type',
        'identification_number',
        'hire_date',
        'termination_date',
        'employment_status',
        'employment_type',
        'base_salary',
        'bank_name',
        'bank_account_number',
        'bank_account_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'tax_information',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'base_salary' => 'decimal:2',
        'tax_information' => 'array',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if employee is active.
     */
    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    /**
     * Get employee positions.
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'employee_positions')
            ->select(['positions.id', 'positions.title', 'positions.code', 'positions.department_id', 'positions.level', 'positions.status'])
            ->withPivot(['start_date', 'end_date', 'salary', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get current position.
     */
    public function currentPosition(): BelongsToMany
    {
        return $this->positions()
            ->wherePivot('status', 'active')
            ->whereNull('employee_positions.end_date');
    }

    /**
     * Get employee payrolls.
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get employee attendance records.
     */
    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get employee leave requests.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get employee benefits.
     */
    public function benefits(): HasMany
    {
        return $this->hasMany(EmployeeBenefit::class);
    }

    /**
     * Get employee performance reviews.
     */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * Get active benefits.
     */
    public function activeBenefits(): HasMany
    {
        return $this->benefits()->where('status', 'active');
    }

    /**
     * Calculate total monthly benefits.
     */
    public function getTotalMonthlyBenefits(): float
    {
        return $this->activeBenefits()
            ->whereIn('frequency', ['monthly', 'biweekly', 'weekly'])
            ->get()
            ->sum(function ($benefit) {
                switch ($benefit->frequency) {
                    case 'weekly':
                        return $benefit->amount * 4.33; // Average weeks per month
                    case 'biweekly':
                        return $benefit->amount * 2.17; // Average biweeks per month
                    default:
                        return $benefit->amount;
                }
            });
    }

    /**
     * Get employee's department through current position (attribute accessor).
     */
    public function getDepartmentAttribute()
    {
        $currentPosition = $this->currentPosition()->first();
        return $currentPosition ? $currentPosition->department : null;
    }
}
