<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Employee::with(['positions:id,title,department_id', 'positions.department:id,name']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('employment_status', $request->status);
            }

            // Filter by department
            if ($request->has('department_id')) {
                $query->whereHas('positions', function($q) use ($request) {
                    $q->where('department_id', $request->department_id)
                      ->where('employee_positions.status', 'active');
                });
            }

            // Filter by position
            if ($request->has('position_id')) {
                $query->whereHas('positions', function($q) use ($request) {
                    $q->where('positions.id', $request->position_id)
                      ->where('employee_positions.status', 'active');
                });
            }

            // Search by name or employee code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_number', 'like', "%{$search}%")
                      ->orWhere('identification_number', 'like', "%{$search}%");
                });
            }

            // Sort by name (default)
            $query->orderBy('first_name')->orderBy('last_name');

            $employees = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $employees,
                'message' => 'Employees retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|string|unique:employees,employee_number',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'identification_type' => 'required|in:DNI,Passport,License',
            'identification_number' => 'required|string|unique:employees,identification_number',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'birth_date' => 'required|date|before:today',
            'hire_date' => 'required|date',
            'position_id' => 'required|exists:positions,id',
            'base_salary' => 'required|numeric|min:0',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'employment_status' => 'required|in:active,inactive,terminated'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $employeeData = $request->except(['position_id']);
            $employee = Employee::create($employeeData);

            // Assign position to employee through pivot table
            if ($request->has('position_id')) {
                $employee->positions()->attach($request->position_id, [
                    'start_date' => $request->hire_date,
                    'salary' => $request->base_salary,
                    'status' => 'active'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $employee->load(['positions:id,title,department_id', 'positions.department:id,name']),
                'message' => 'Employee created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified employee.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $employee = Employee::with([
                'positions.department:id,name,code',
                'positions:id,title,code,department_id',
                'benefits' => function($query) {
                    $query->where('status', 'active')
                          ->with('payrollConcept:id,name,code,type');
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => 'Employee retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => [
                'sometimes',
                'string',
                Rule::unique('employees', 'employee_number')->ignore($id)
            ],
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'identification_type' => 'sometimes|in:DNI,Passport,License',
            'identification_number' => [
                'sometimes',
                'string',
                Rule::unique('employees', 'identification_number')->ignore($id)
            ],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('employees', 'email')->ignore($id)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'birth_date' => 'sometimes|date|before:today',
            'hire_date' => 'sometimes|date',
            'position_id' => 'sometimes|exists:positions,id',
            'base_salary' => 'sometimes|numeric|min:0',
            'employment_type' => 'sometimes|in:full_time,part_time,contract,intern',
            'employment_status' => 'sometimes|in:active,inactive,terminated',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'termination_reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($id);
            
            DB::beginTransaction();

            $employeeData = $request->except(['position_id']);
            $employee->update($employeeData);

            // Handle position change if provided
            if ($request->has('position_id')) {
                // End current active position
                $currentPosition = $employee->positions()->wherePivot('status', 'active')->first();
                if ($currentPosition) {
                    $employee->positions()->updateExistingPivot($currentPosition->id, [
                        'status' => 'inactive',
                        'end_date' => now()
                    ]);
                }
                
                // Add new position
                $employee->positions()->attach($request->position_id, [
                    'start_date' => now(),
                    'salary' => $request->base_salary ?? $employee->base_salary,
                    'status' => 'active'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $employee->load(['positions:id,title,department_id', 'positions.department:id,name']),
                'message' => 'Employee updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified employee (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($id);

            // Check if employee has active payrolls
            $activePayrolls = $employee->payrolls()->whereIn('status', ['calculated', 'approved'])->count();
            
            if ($activePayrolls > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete employee with active payrolls'
                ], 409);
            }

            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee payroll history.
     */
    public function payrollHistory(Request $request, string $id): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($id);
            
            $query = $employee->payrolls()->with(['payrollPeriod:id,name,start_date,end_date']);
            
            // Filter by year
            if ($request->has('year')) {
                $query->whereHas('period', function($q) use ($request) {
                    $q->whereYear('start_date', $request->year);
                });
            }
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            $payrolls = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee->only(['id', 'first_name', 'last_name', 'employee_number']),
                    'payrolls' => $payrolls
                ],
                'message' => 'Employee payroll history retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee payroll history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payroll history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee benefits.
     */
    public function benefits(string $id): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($id);
            
            $benefits = $employee->benefits()
                                ->with('payrollConcept:id,name,code,type,description')
                                ->orderBy('effective_date', 'desc')
                                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee->only(['id', 'first_name', 'last_name', 'employee_number']),
                    'benefits' => $benefits
                ],
                'message' => 'Employee benefits retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee benefits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving employee benefits',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
