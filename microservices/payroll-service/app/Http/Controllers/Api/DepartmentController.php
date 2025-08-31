<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Department::query();

            // Search by name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Include employee count if requested
            if ($request->boolean('with_employee_count')) {
                $query->withCount('employees');
            }

            // Sort by name (default ascending)
            $query->orderBy('name', 'asc');

            $departments = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $departments,
                'message' => 'Departments retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving departments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'required|string|max:50|unique:departments,code',
            'description' => 'nullable|string|max:1000',
            'manager_id' => 'nullable|exists:employees,id',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
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

            $departmentData = $request->all();
            $departmentData['is_active'] = $request->get('is_active', true);

            $department = Department::create($departmentData);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $department->load('manager:id,first_name,last_name,employee_code'),
                'message' => 'Department created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating department: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified department.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $department = Department::with([
                'manager:id,first_name,last_name,employee_code,email',
                'employees:id,first_name,last_name,employee_code,department_id,position_id',
                'employees.position:id,name'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $department,
                'message' => 'Department retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving department: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Department not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:departments,name,' . $id,
            'code' => 'sometimes|string|max:50|unique:departments,code,' . $id,
            'description' => 'nullable|string|max:1000',
            'manager_id' => 'nullable|exists:employees,id',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $department = Department::findOrFail($id);

            DB::beginTransaction();

            $department->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $department->load('manager:id,first_name,last_name,employee_code'),
                'message' => 'Department updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating department: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified department.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);

            // Check if department has employees
            $employeeCount = Employee::where('department_id', $id)->count();
            if ($employeeCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete department with active employees. Please reassign employees first.'
                ], 409);
            }

            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting department: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department statistics.
     */
    public function statistics(string $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);
            
            $stats = [
                'total_employees' => Employee::where('department_id', $id)->count(),
                'active_employees' => Employee::where('department_id', $id)
                                            ->where('is_active', true)
                                            ->count(),
                'positions' => Employee::where('department_id', $id)
                                     ->with('position:id,name')
                                     ->get()
                                     ->groupBy('position.name')
                                     ->map(function($employees) {
                                         return $employees->count();
                                     }),
                'average_salary' => Employee::where('department_id', $id)
                                          ->avg('base_salary') ?? 0,
                'total_payroll_cost' => Employee::where('department_id', $id)
                                              ->sum('base_salary') ?? 0
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => $department->only(['id', 'name', 'code']),
                    'statistics' => $stats
                ],
                'message' => 'Department statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving department statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving department statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all employees in a department.
     */
    public function employees(Request $request, string $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);
            
            $query = Employee::where('department_id', $id)
                           ->with(['position:id,name', 'department:id,name']);

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by employee name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            $employees = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => $department->only(['id', 'name', 'code']),
                    'employees' => $employees
                ],
                'message' => 'Department employees retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving department employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving department employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
