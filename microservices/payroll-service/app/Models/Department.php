<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'code',
        'manager_id',
        'budget',
        'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
    ];



    /**
     * Get the department manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get department positions.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Get active positions.
     */
    public function activePositions(): HasMany
    {
        return $this->positions()->where('status', 'active');
    }

    /**
     * Get department employees through positions.
     */
    public function employees()
    {
        return Employee::whereHas('positions', function ($query) {
            $query->where('department_id', $this->id)
                  ->where('employee_positions.status', 'active')
                  ->whereNull('employee_positions.end_date');
        });
    }

    /**
     * Check if department is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get total department employees count.
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->count();
    }

    /**
     * Get department's total payroll cost.
     */
    public function getTotalPayrollCost(): float
    {
        return $this->employees()->get()->sum('base_salary');
    }
}
