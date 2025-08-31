<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    /**
     * Display a listing of positions with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Position::query();

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

            // Filter by salary range
            if ($request->has('min_salary')) {
                $query->where('min_salary', '>=', $request->min_salary);
            }

            if ($request->has('max_salary')) {
                $query->where('max_salary', '<=', $request->max_salary);
            }

            // Include employee count if requested
            if ($request->boolean('with_employee_count')) {
                $query->withCount('employees');
            }

            // Sort by name (default ascending)
            $query->orderBy('name', 'asc');

            $positions = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $positions,
                'message' => 'Positions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving positions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving positions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created position.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:positions,name',
            'code' => 'required|string|max:50|unique:positions,code',
            'description' => 'nullable|string|max:1000',
            'min_salary' => 'required|numeric|min:0',
            'max_salary' => 'required|numeric|min:0|gte:min_salary',
            'level' => 'required|integer|min:1|max:10',
            'requirements' => 'nullable|string|max:2000',
            'responsibilities' => 'nullable|string|max:2000',
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

            $positionData = $request->all();
            $positionData['is_active'] = $request->get('is_active', true);

            $position = Position::create($positionData);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $position,
                'message' => 'Position created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating position: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating position',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified position.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $position = Position::with([
                'employees:id,first_name,last_name,employee_code,position_id,department_id,base_salary',
                'employees.department:id,name'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $position,
                'message' => 'Position retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving position: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Position not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified position.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:positions,name,' . $id,
            'code' => 'sometimes|string|max:50|unique:positions,code,' . $id,
            'description' => 'nullable|string|max:1000',
            'min_salary' => 'sometimes|numeric|min:0',
            'max_salary' => 'sometimes|numeric|min:0',
            'level' => 'sometimes|integer|min:1|max:10',
            'requirements' => 'nullable|string|max:2000',
            'responsibilities' => 'nullable|string|max:2000',
            'is_active' => 'boolean'
        ]);

        // Custom validation for salary range
        $validator->after(function ($validator) use ($request, $id) {
            $position = Position::find($id);
            if ($position) {
                $minSalary = $request->get('min_salary', $position->min_salary);
                $maxSalary = $request->get('max_salary', $position->max_salary);
                
                if ($maxSalary < $minSalary) {
                    $validator->errors()->add('max_salary', 'Maximum salary must be greater than or equal to minimum salary.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $position = Position::findOrFail($id);

            DB::beginTransaction();

            $position->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $position,
                'message' => 'Position updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating position: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating position',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified position.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $position = Position::findOrFail($id);

            // Check if position has employees
            $employeeCount = Employee::where('position_id', $id)->count();
            if ($employeeCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete position with active employees. Please reassign employees first.'
                ], 409);
            }

            $position->delete();

            return response()->json([
                'success' => true,
                'message' => 'Position deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting position: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting position',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get position statistics.
     */
    public function statistics(string $id): JsonResponse
    {
        try {
            $position = Position::findOrFail($id);
            
            $employees = Employee::where('position_id', $id)->get();
            
            $stats = [
                'total_employees' => $employees->count(),
                'active_employees' => $employees->where('is_active', true)->count(),
                'departments' => $employees->groupBy('department_id')
                                        ->map(function($deptEmployees) {
                                            return $deptEmployees->count();
                                        }),
                'salary_statistics' => [
                    'average_salary' => $employees->avg('base_salary') ?? 0,
                    'min_salary_actual' => $employees->min('base_salary') ?? 0,
                    'max_salary_actual' => $employees->max('base_salary') ?? 0,
                    'total_payroll_cost' => $employees->sum('base_salary') ?? 0,
                    'position_min_salary' => $position->min_salary,
                    'position_max_salary' => $position->max_salary
                ],
                'salary_compliance' => [
                    'below_min' => $employees->where('base_salary', '<', $position->min_salary)->count(),
                    'above_max' => $employees->where('base_salary', '>', $position->max_salary)->count(),
                    'within_range' => $employees->whereBetween('base_salary', [$position->min_salary, $position->max_salary])->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'position' => $position->only(['id', 'name', 'code', 'level']),
                    'statistics' => $stats
                ],
                'message' => 'Position statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving position statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving position statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all employees in a position.
     */
    public function employees(Request $request, string $id): JsonResponse
    {
        try {
            $position = Position::findOrFail($id);
            
            $query = Employee::where('position_id', $id)
                           ->with(['department:id,name', 'position:id,name']);

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by department
            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
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
                    'position' => $position->only(['id', 'name', 'code', 'level', 'min_salary', 'max_salary']),
                    'employees' => $employees
                ],
                'message' => 'Position employees retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving position employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving position employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get salary recommendations for a position.
     */
    public function salaryRecommendations(string $id): JsonResponse
    {
        try {
            $position = Position::findOrFail($id);
            
            $employees = Employee::where('position_id', $id)
                               ->where('is_active', true)
                               ->get();
            
            $recommendations = [
                'position_range' => [
                    'min' => $position->min_salary,
                    'max' => $position->max_salary,
                    'midpoint' => ($position->min_salary + $position->max_salary) / 2
                ],
                'current_employees' => [
                    'count' => $employees->count(),
                    'average' => $employees->avg('base_salary') ?? 0,
                    'median' => $employees->count() > 0 ? $employees->sortBy('base_salary')->values()->get(intval($employees->count() / 2))->base_salary ?? 0 : 0,
                    'min' => $employees->min('base_salary') ?? 0,
                    'max' => $employees->max('base_salary') ?? 0
                ],
                'recommendations' => [
                    'entry_level' => $position->min_salary + ($position->max_salary - $position->min_salary) * 0.1,
                    'experienced' => $position->min_salary + ($position->max_salary - $position->min_salary) * 0.6,
                    'senior' => $position->min_salary + ($position->max_salary - $position->min_salary) * 0.9
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'position' => $position->only(['id', 'name', 'code', 'level']),
                    'salary_recommendations' => $recommendations
                ],
                'message' => 'Salary recommendations retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving salary recommendations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving salary recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
