<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\EmployeePosition;
use App\Models\EmployeeBenefit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmployeeService
{
    /**
     * Create a new employee
     */
    public function createEmployee(array $data): Employee
    {
        DB::beginTransaction();
        
        try {
            $employee = Employee::create([
                'employee_number' => $this->generateEmployeeNumber(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'document_type' => $data['document_type'],
                'document_number' => $data['document_number'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'hire_date' => $data['hire_date'],
                'status' => $data['status'] ?? 'active',
                'bank_account' => $data['bank_account'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null
            ]);

            // Assign position if provided
            if (isset($data['position_id'])) {
                $this->assignPosition($employee, $data['position_id'], $data['base_salary'] ?? null);
            }

            // Assign benefits if provided
            if (isset($data['benefits']) && is_array($data['benefits'])) {
                $this->assignBenefits($employee, $data['benefits']);
            }

            DB::commit();
            
            Log::info("Employee created successfully: {$employee->id}");
            
            return $employee->fresh(['currentPosition', 'department', 'benefits']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating employee: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an employee
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        DB::beginTransaction();
        
        try {
            $employee->update([
                'first_name' => $data['first_name'] ?? $employee->first_name,
                'last_name' => $data['last_name'] ?? $employee->last_name,
                'email' => $data['email'] ?? $employee->email,
                'phone' => $data['phone'] ?? $employee->phone,
                'address' => $data['address'] ?? $employee->address,
                'birth_date' => $data['birth_date'] ?? $employee->birth_date,
                'status' => $data['status'] ?? $employee->status,
                'bank_account' => $data['bank_account'] ?? $employee->bank_account,
                'bank_name' => $data['bank_name'] ?? $employee->bank_name,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? $employee->emergency_contact_name,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? $employee->emergency_contact_phone
            ]);

            // Update position if provided
            if (isset($data['position_id'])) {
                $this->updatePosition($employee, $data['position_id'], $data['base_salary'] ?? null);
            }

            // Update benefits if provided
            if (isset($data['benefits']) && is_array($data['benefits'])) {
                $this->updateBenefits($employee, $data['benefits']);
            }

            DB::commit();
            
            Log::info("Employee updated successfully: {$employee->id}");
            
            return $employee->fresh(['currentPosition', 'department', 'benefits']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating employee: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign a position to an employee
     */
    public function assignPosition(Employee $employee, int $positionId, ?float $baseSalary = null): EmployeePosition
    {
        $position = Position::findOrFail($positionId);
        
        // End current position if exists
        $currentPosition = $employee->currentPosition();
        if ($currentPosition) {
            $currentPosition->update([
                'is_current' => false,
                'end_date' => now()
            ]);
        }

        // Create new position assignment
        $employeePosition = EmployeePosition::create([
            'employee_id' => $employee->id,
            'position_id' => $positionId,
            'department_id' => $position->department_id,
            'base_salary' => $baseSalary ?? $position->base_salary,
            'start_date' => now(),
            'is_current' => true
        ]);

        Log::info("Position assigned to employee {$employee->id}: position {$positionId}");
        
        return $employeePosition;
    }

    /**
     * Update employee position
     */
    public function updatePosition(Employee $employee, int $positionId, ?float $baseSalary = null): EmployeePosition
    {
        $currentPosition = $employee->currentPosition();
        
        if (!$currentPosition || $currentPosition->position_id != $positionId) {
            return $this->assignPosition($employee, $positionId, $baseSalary);
        }

        // Update salary if provided
        if ($baseSalary !== null) {
            $currentPosition->update(['base_salary' => $baseSalary]);
        }

        return $currentPosition;
    }

    /**
     * Assign benefits to an employee
     */
    public function assignBenefits(Employee $employee, array $benefits): void
    {
        foreach ($benefits as $benefit) {
            EmployeeBenefit::create([
                'employee_id' => $employee->id,
                'benefit_type' => $benefit['type'],
                'benefit_name' => $benefit['name'],
                'amount' => $benefit['amount'] ?? 0,
                'percentage' => $benefit['percentage'] ?? null,
                'start_date' => $benefit['start_date'] ?? now(),
                'end_date' => $benefit['end_date'] ?? null,
                'is_active' => $benefit['is_active'] ?? true
            ]);
        }
    }

    /**
     * Update employee benefits
     */
    public function updateBenefits(Employee $employee, array $benefits): void
    {
        // Remove existing benefits
        $employee->benefits()->delete();
        
        // Add new benefits
        $this->assignBenefits($employee, $benefits);
    }

    /**
     * Deactivate an employee
     */
    public function deactivateEmployee(Employee $employee, ?string $reason = null): Employee
    {
        DB::beginTransaction();
        
        try {
            $employee->update([
                'status' => 'inactive',
                'termination_date' => now(),
                'termination_reason' => $reason
            ]);

            // End current position
            $currentPosition = $employee->currentPosition();
            if ($currentPosition) {
                $currentPosition->update([
                    'is_current' => false,
                    'end_date' => now()
                ]);
            }

            // Deactivate benefits
            $employee->benefits()->update([
                'is_active' => false,
                'end_date' => now()
            ]);

            DB::commit();
            
            Log::info("Employee deactivated: {$employee->id}");
            
            return $employee->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deactivating employee: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reactivate an employee
     */
    public function reactivateEmployee(Employee $employee): Employee
    {
        $employee->update([
            'status' => 'active',
            'termination_date' => null,
            'termination_reason' => null
        ]);

        Log::info("Employee reactivated: {$employee->id}");
        
        return $employee->fresh();
    }

    /**
     * Generate a unique employee number
     */
    private function generateEmployeeNumber(): string
    {
        $year = date('Y');
        $lastEmployee = Employee::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastEmployee ? (int)substr($lastEmployee->employee_number, -4) + 1 : 1;
        
        return sprintf('EMP%s%04d', $year, $sequence);
    }

    /**
     * Get employee statistics
     */
    public function getEmployeeStats(): array
    {
        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'inactive_employees' => Employee::where('status', 'inactive')->count(),
            'employees_by_department' => Employee::join('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->join('departments', 'employee_positions.department_id', '=', 'departments.id')
                ->where('employee_positions.is_current', true)
                ->where('employees.status', 'active')
                ->groupBy('departments.id', 'departments.name')
                ->selectRaw('departments.name, COUNT(*) as count')
                ->pluck('count', 'name')
                ->toArray(),
            'recent_hires' => Employee::where('hire_date', '>=', now()->subDays(30))->count(),
            'upcoming_birthdays' => Employee::whereRaw('DATE_FORMAT(birth_date, "%m-%d") BETWEEN ? AND ?', [
                now()->format('m-d'),
                now()->addDays(30)->format('m-d')
            ])->count()
        ];
    }

    /**
     * Search employees
     */
    public function searchEmployees(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = Employee::with(['currentPosition.position', 'currentPosition.department']);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['department_id'])) {
            $query->whereHas('currentPosition', function($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (isset($filters['position_id'])) {
            $query->whereHas('currentPosition', function($q) use ($filters) {
                $q->where('position_id', $filters['position_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['hire_date_from'])) {
            $query->where('hire_date', '>=', $filters['hire_date_from']);
        }

        if (isset($filters['hire_date_to'])) {
            $query->where('hire_date', '<=', $filters['hire_date_to']);
        }

        return $query->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get();
    }

    /**
     * Get employees with upcoming birthdays
     */
    public function getUpcomingBirthdays(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::where('status', 'active')
            ->whereNotNull('birth_date')
            ->whereRaw('DATE_FORMAT(birth_date, "%m-%d") BETWEEN ? AND ?', [
                now()->format('m-d'),
                now()->addDays($days)->format('m-d')
            ])
            ->orderByRaw('DATE_FORMAT(birth_date, "%m-%d")')
            ->get();
    }

    /**
     * Get employees with work anniversaries
     */
    public function getWorkAnniversaries(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::where('status', 'active')
            ->whereRaw('DATE_FORMAT(hire_date, "%m-%d") BETWEEN ? AND ?', [
                now()->format('m-d'),
                now()->addDays($days)->format('m-d')
            ])
            ->orderByRaw('DATE_FORMAT(hire_date, "%m-%d")')
            ->get();
    }
}