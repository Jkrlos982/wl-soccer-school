<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'code',
        'department_id',
        'min_salary',
        'max_salary',
        'requirements',
        'responsibilities',
        'level',
        'status',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'requirements' => 'array',
        'responsibilities' => 'array',
    ];



    /**
     * Get the department that owns the position.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get employees assigned to this position.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_positions')
            ->withPivot(['start_date', 'end_date', 'salary', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get current employees in this position.
     */
    public function currentEmployees(): BelongsToMany
    {
        return $this->employees()
            ->wherePivot('status', 'active')
            ->whereNull('employee_positions.end_date');
    }

    /**
     * Check if position is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get name attribute (alias for title).
     */
    public function getNameAttribute(): string
    {
        return $this->title;
    }

    /**
     * Get salary range as formatted string.
     */
    public function getSalaryRangeAttribute(): string
    {
        return number_format($this->min_salary, 0) . ' - ' . number_format($this->max_salary, 0);
    }

    /**
     * Check if salary is within position range.
     */
    public function isValidSalary(float $salary): bool
    {
        return $salary >= $this->min_salary && $salary <= $this->max_salary;
    }

    /**
     * Get current employee count.
     */
    public function getCurrentEmployeeCountAttribute(): int
    {
        return $this->currentEmployees()->count();
    }

    /**
     * Get average salary for this position.
     */
    public function getAverageSalary(): float
    {
        return $this->currentEmployees()->avg('employee_positions.salary') ?? 0;
    }
}
