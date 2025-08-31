<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PayrollReportController extends Controller
{
    /**
     * Get payroll summary report.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period_id' => 'nullable|exists:payroll_periods,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'status' => 'nullable|in:draft,approved,paid',
            'report_type' => 'nullable|in:summary,detailed,comparative'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Payroll::with([
                'employee:id,first_name,last_name,employee_code,department_id,position_id',
                'employee.department:id,name',
                'employee.position:id,name',
                'payrollPeriod:id,name,start_date,end_date,status'
            ]);

            // Apply filters
            if ($request->has('period_id')) {
                $query->where('payroll_period_id', $request->period_id);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereHas('payrollPeriod', function($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date]);
                });
            }

            if ($request->has('department_id')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            if ($request->has('position_id')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('position_id', $request->position_id);
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $payrolls = $query->get();

            $reportType = $request->get('report_type', 'summary');

            switch ($reportType) {
                case 'detailed':
                    $report = $this->generateDetailedReport($payrolls);
                    break;
                case 'comparative':
                    $report = $this->generateComparativeReport($request);
                    break;
                default:
                    $report = $this->generateSummaryReport($payrolls);
            }

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Payroll report generated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating payroll report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating payroll report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department-wise payroll report.
     */
    public function departmentReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period_id' => 'nullable|exists:payroll_periods,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Payroll::with([
                'employee.department',
                'payrollPeriod'
            ]);

            // Apply date filters
            if ($request->has('period_id')) {
                $query->where('payroll_period_id', $request->period_id);
            } elseif ($request->has('start_date') && $request->has('end_date')) {
                $query->whereHas('payrollPeriod', function($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date]);
                });
            }

            $payrolls = $query->get();

            $departmentReport = $payrolls->groupBy('employee.department.name')
                ->map(function($deptPayrolls, $deptName) {
                    return [
                        'department_name' => $deptName,
                        'employee_count' => $deptPayrolls->count(),
                        'total_gross_salary' => $deptPayrolls->sum('gross_salary'),
                        'total_deductions' => $deptPayrolls->sum('total_deductions'),
                        'total_net_salary' => $deptPayrolls->sum('net_salary'),
                        'average_gross_salary' => $deptPayrolls->avg('gross_salary'),
                        'average_net_salary' => $deptPayrolls->avg('net_salary'),
                        'status_breakdown' => $deptPayrolls->groupBy('status')
                            ->map(function($statusPayrolls) {
                                return $statusPayrolls->count();
                            })
                    ];
                })->values();

            $summary = [
                'total_departments' => $departmentReport->count(),
                'total_employees' => $payrolls->count(),
                'grand_total_gross' => $payrolls->sum('gross_salary'),
                'grand_total_deductions' => $payrolls->sum('total_deductions'),
                'grand_total_net' => $payrolls->sum('net_salary')
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'departments' => $departmentReport
                ],
                'message' => 'Department payroll report generated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating department report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating department report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get position-wise payroll report.
     */
    public function positionReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period_id' => 'nullable|exists:payroll_periods,id',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Payroll::with([
                'employee.position',
                'employee.department',
                'payrollPeriod'
            ]);

            if ($request->has('period_id')) {
                $query->where('payroll_period_id', $request->period_id);
            }

            if ($request->has('department_id')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            $payrolls = $query->get();

            $positionReport = $payrolls->groupBy('employee.position.name')
                ->map(function($posPayrolls, $posName) {
                    return [
                        'position_name' => $posName,
                        'employee_count' => $posPayrolls->count(),
                        'total_gross_salary' => $posPayrolls->sum('gross_salary'),
                        'total_net_salary' => $posPayrolls->sum('net_salary'),
                        'average_gross_salary' => $posPayrolls->avg('gross_salary'),
                        'average_net_salary' => $posPayrolls->avg('net_salary'),
                        'min_salary' => $posPayrolls->min('gross_salary'),
                        'max_salary' => $posPayrolls->max('gross_salary'),
                        'departments' => $posPayrolls->groupBy('employee.department.name')
                            ->map(function($deptPayrolls) {
                                return $deptPayrolls->count();
                            })
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => $positionReport,
                'message' => 'Position payroll report generated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating position report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating position report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee payroll history.
     */
    public function employeeHistory(Request $request, string $employeeId): JsonResponse
    {
        $validator = Validator::make(array_merge($request->all(), ['employee_id' => $employeeId]), [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::with(['department', 'position'])->findOrFail($employeeId);

            $query = Payroll::where('employee_id', $employeeId)
                ->with(['payrollPeriod', 'payrollConcepts'])
                ->orderBy('created_at', 'desc');

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereHas('payrollPeriod', function($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date]);
                });
            }

            $limit = $request->get('limit', 12);
            $payrolls = $query->limit($limit)->get();

            $summary = [
                'total_payrolls' => $payrolls->count(),
                'total_gross_earned' => $payrolls->sum('gross_salary'),
                'total_deductions' => $payrolls->sum('total_deductions'),
                'total_net_earned' => $payrolls->sum('net_salary'),
                'average_gross' => $payrolls->avg('gross_salary'),
                'average_net' => $payrolls->avg('net_salary'),
                'highest_gross' => $payrolls->max('gross_salary'),
                'lowest_gross' => $payrolls->min('gross_salary')
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee,
                    'summary' => $summary,
                    'payroll_history' => $payrolls
                ],
                'message' => 'Employee payroll history retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee payroll history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving employee payroll history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payroll analytics and trends.
     */
    public function analytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'months' => 'nullable|integer|min:1|max:24',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $months = $request->get('months', 12);
            $startDate = Carbon::now()->subMonths($months)->startOfMonth();

            $query = Payroll::with(['employee.department', 'payrollPeriod'])
                ->whereHas('payrollPeriod', function($q) use ($startDate) {
                    $q->where('start_date', '>=', $startDate);
                });

            if ($request->has('department_id')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            $payrolls = $query->get();

            // Monthly trends
            $monthlyTrends = $payrolls->groupBy(function($payroll) {
                return Carbon::parse($payroll->payrollPeriod->start_date)->format('Y-m');
            })->map(function($monthPayrolls, $month) {
                return [
                    'month' => $month,
                    'employee_count' => $monthPayrolls->count(),
                    'total_gross' => $monthPayrolls->sum('gross_salary'),
                    'total_net' => $monthPayrolls->sum('net_salary'),
                    'total_deductions' => $monthPayrolls->sum('total_deductions'),
                    'average_gross' => $monthPayrolls->avg('gross_salary'),
                    'average_net' => $monthPayrolls->avg('net_salary')
                ];
            })->sortBy('month')->values();

            // Department comparison
            $departmentComparison = $payrolls->groupBy('employee.department.name')
                ->map(function($deptPayrolls, $deptName) {
                    return [
                        'department' => $deptName,
                        'employee_count' => $deptPayrolls->count(),
                        'total_cost' => $deptPayrolls->sum('net_salary'),
                        'average_salary' => $deptPayrolls->avg('net_salary'),
                        'percentage_of_total' => 0 // Will be calculated below
                    ];
                });

            $totalPayrollCost = $payrolls->sum('net_salary');
            $departmentComparison = $departmentComparison->map(function($dept) use ($totalPayrollCost) {
                $dept['percentage_of_total'] = $totalPayrollCost > 0 ? ($dept['total_cost'] / $totalPayrollCost) * 100 : 0;
                return $dept;
            })->values();

            $analytics = [
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => Carbon::now()->format('Y-m-d'),
                    'months_analyzed' => $months
                ],
                'overall_summary' => [
                    'total_payrolls' => $payrolls->count(),
                    'total_gross_paid' => $payrolls->sum('gross_salary'),
                    'total_net_paid' => $payrolls->sum('net_salary'),
                    'total_deductions' => $payrolls->sum('total_deductions'),
                    'average_monthly_cost' => $monthlyTrends->avg('total_net'),
                    'unique_employees' => $payrolls->pluck('employee_id')->unique()->count()
                ],
                'monthly_trends' => $monthlyTrends,
                'department_comparison' => $departmentComparison
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Payroll analytics generated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating payroll analytics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating payroll analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate summary report.
     */
    private function generateSummaryReport($payrolls): array
    {
        return [
            'total_payrolls' => $payrolls->count(),
            'total_gross_salary' => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'average_gross_salary' => $payrolls->avg('gross_salary'),
            'average_net_salary' => $payrolls->avg('net_salary'),
            'status_breakdown' => $payrolls->groupBy('status')
                ->map(function($statusPayrolls) {
                    return $statusPayrolls->count();
                }),
            'department_breakdown' => $payrolls->groupBy('employee.department.name')
                ->map(function($deptPayrolls) {
                    return [
                        'count' => $deptPayrolls->count(),
                        'total_net' => $deptPayrolls->sum('net_salary')
                    ];
                })
        ];
    }

    /**
     * Generate detailed report.
     */
    private function generateDetailedReport($payrolls): array
    {
        return [
            'summary' => $this->generateSummaryReport($payrolls),
            'detailed_payrolls' => $payrolls->map(function($payroll) {
                return [
                    'id' => $payroll->id,
                    'employee' => [
                        'id' => $payroll->employee->id,
                        'name' => $payroll->employee->first_name . ' ' . $payroll->employee->last_name,
                        'employee_code' => $payroll->employee->employee_code,
                        'department' => $payroll->employee->department->name ?? null,
                        'position' => $payroll->employee->position->name ?? null
                    ],
                    'period' => [
                        'name' => $payroll->payrollPeriod->name,
                        'start_date' => $payroll->payrollPeriod->start_date,
                        'end_date' => $payroll->payrollPeriod->end_date
                    ],
                    'amounts' => [
                        'gross_salary' => $payroll->gross_salary,
                        'total_deductions' => $payroll->total_deductions,
                        'net_salary' => $payroll->net_salary
                    ],
                    'status' => $payroll->status,
                    'created_at' => $payroll->created_at
                ];
            })
        ];
    }

    /**
     * Generate comparative report.
     */
    private function generateComparativeReport($request): array
    {
        // This would compare current period with previous period
        // Implementation would depend on specific business requirements
        return [
            'message' => 'Comparative report functionality to be implemented based on specific requirements'
        ];
    }
}
