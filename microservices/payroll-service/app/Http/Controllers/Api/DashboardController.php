<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard analytics data.
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $currentPeriod = PayrollPeriod::where('status', 'active')
                ->orWhere('status', 'open')
                ->first();

            $data = [
                'overview' => $this->getOverviewStats($currentPeriod),
                'payroll_summary' => $this->getPayrollSummary($currentPeriod),
                'employee_stats' => $this->getEmployeeStats(),
                'attendance_summary' => $this->getAttendanceSummary(),
                'leave_requests' => $this->getLeaveRequestsSummary(),
                'recent_activities' => $this->getRecentActivities(),
                'charts_data' => $this->getChartsData()
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Dashboard analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving dashboard analytics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overview statistics.
     */
    private function getOverviewStats($currentPeriod): array
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        $totalDepartments = Department::where('is_active', true)->count();
        
        $currentPayrolls = 0;
        $totalPayrollAmount = 0;
        
        if ($currentPeriod) {
            $payrollData = Payroll::where('payroll_period_id', $currentPeriod->id)
                ->selectRaw('COUNT(*) as count, SUM(net_salary) as total')
                ->first();
            
            $currentPayrolls = $payrollData->count ?? 0;
            $totalPayrollAmount = $payrollData->total ?? 0;
        }

        $pendingLeaveRequests = LeaveRequest::where('status', 'pending')->count();

        return [
            'total_employees' => $totalEmployees,
            'total_departments' => $totalDepartments,
            'current_payrolls' => $currentPayrolls,
            'total_payroll_amount' => $totalPayrollAmount,
            'pending_leave_requests' => $pendingLeaveRequests,
            'current_period' => $currentPeriod ? [
                'id' => $currentPeriod->id,
                'name' => $currentPeriod->name,
                'start_date' => $currentPeriod->start_date,
                'end_date' => $currentPeriod->end_date,
                'status' => $currentPeriod->status
            ] : null
        ];
    }

    /**
     * Get payroll summary for current period.
     */
    private function getPayrollSummary($currentPeriod): array
    {
        if (!$currentPeriod) {
            return [
                'total_payrolls' => 0,
                'processed' => 0,
                'pending' => 0,
                'approved' => 0,
                'paid' => 0,
                'total_gross' => 0,
                'total_deductions' => 0,
                'total_net' => 0
            ];
        }

        $payrollStats = Payroll::where('payroll_period_id', $currentPeriod->id)
            ->selectRaw('
                COUNT(*) as total_payrolls,
                SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as processed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid,
                SUM(gross_salary) as total_gross,
                SUM(total_deductions) as total_deductions,
                SUM(net_salary) as total_net
            ')
            ->first();

        return [
            'total_payrolls' => $payrollStats->total_payrolls ?? 0,
            'processed' => $payrollStats->processed ?? 0,
            'pending' => $payrollStats->pending ?? 0,
            'approved' => $payrollStats->approved ?? 0,
            'paid' => $payrollStats->paid ?? 0,
            'total_gross' => $payrollStats->total_gross ?? 0,
            'total_deductions' => $payrollStats->total_deductions ?? 0,
            'total_net' => $payrollStats->total_net ?? 0
        ];
    }

    /**
     * Get employee statistics.
     */
    private function getEmployeeStats(): array
    {
        $totalActive = Employee::where('is_active', true)->count();
        $totalInactive = Employee::where('is_active', false)->count();
        
        $departmentStats = Employee::where('is_active', true)
            ->join('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->join('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->join('departments', 'positions.department_id', '=', 'departments.id')
            ->where('employee_positions.status', 'active')
            ->whereNull('employee_positions.end_date')
            ->select('departments.name', DB::raw('COUNT(DISTINCT employees.id) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $positionStats = Employee::where('is_active', true)
            ->join('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->join('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->where('employee_positions.status', 'active')
            ->whereNull('employee_positions.end_date')
            ->select('positions.title', DB::raw('COUNT(DISTINCT employees.id) as count'))
            ->groupBy('positions.id', 'positions.title')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_active' => $totalActive,
            'total_inactive' => $totalInactive,
            'by_department' => $departmentStats,
            'by_position' => $positionStats
        ];
    }

    /**
     * Get attendance summary for current month.
     */
    private function getAttendanceSummary(): array
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        $attendanceStats = Attendance::whereRaw('DATE_FORMAT(date, "%Y-%m") = ?', [$currentMonth])
            ->selectRaw('
                COUNT(*) as total_records,
                COUNT(DISTINCT employee_id) as employees_with_records,
                AVG(worked_hours) as avg_worked_hours,
                SUM(overtime_hours) as total_overtime,
                COUNT(CASE WHEN status = "present" THEN 1 END) as present_days,
                COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_days,
                COUNT(CASE WHEN status = "late" THEN 1 END) as late_days
            ')
            ->first();

        return [
            'total_records' => $attendanceStats->total_records ?? 0,
            'employees_with_records' => $attendanceStats->employees_with_records ?? 0,
            'avg_worked_hours' => round($attendanceStats->avg_worked_hours ?? 0, 2),
            'total_overtime' => $attendanceStats->total_overtime ?? 0,
            'present_days' => $attendanceStats->present_days ?? 0,
            'absent_days' => $attendanceStats->absent_days ?? 0,
            'late_days' => $attendanceStats->late_days ?? 0
        ];
    }

    /**
     * Get leave requests summary.
     */
    private function getLeaveRequestsSummary(): array
    {
        $currentYear = Carbon::now()->year;
        
        $leaveStats = LeaveRequest::whereYear('start_date', $currentYear)
            ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved,
                COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected,
                SUM(requested_days) as total_days_requested
            ')
            ->first();

        $leaveTypeStats = LeaveRequest::whereYear('start_date', $currentYear)
            ->where('status', 'approved')
            ->select('leave_type', DB::raw('COUNT(*) as count, SUM(requested_days) as total_days'))
            ->groupBy('leave_type')
            ->get();

        return [
            'total_requests' => $leaveStats->total_requests ?? 0,
            'pending' => $leaveStats->pending ?? 0,
            'approved' => $leaveStats->approved ?? 0,
            'rejected' => $leaveStats->rejected ?? 0,
            'total_days_requested' => $leaveStats->total_days_requested ?? 0,
            'by_type' => $leaveTypeStats
        ];
    }

    /**
     * Get recent activities.
     */
    private function getRecentActivities(): array
    {
        $activities = [];

        // Recent payrolls
        $recentPayrolls = Payroll::with(['employee:id,first_name,last_name', 'payrollPeriod:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($payroll) {
                return [
                    'type' => 'payroll',
                    'description' => "Nómina procesada para {$payroll->employee->first_name} {$payroll->employee->last_name}",
                    'date' => $payroll->created_at,
                    'status' => $payroll->status
                ];
            });

        // Recent leave requests
        $recentLeaves = LeaveRequest::with(['employee:id,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($leave) {
                return [
                    'type' => 'leave_request',
                    'description' => "Solicitud de {$leave->leave_type} de {$leave->employee->first_name} {$leave->employee->last_name}",
                    'date' => $leave->created_at,
                    'status' => $leave->status
                ];
            });

        $activities = $recentPayrolls->concat($recentLeaves)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return $activities->toArray();
    }

    /**
     * Get charts data for dashboard.
     */
    private function getChartsData(): array
    {
        // Monthly payroll trend (last 6 months)
        $monthlyPayroll = Payroll::join('payroll_periods', 'payrolls.payroll_period_id', '=', 'payroll_periods.id')
            ->where('payroll_periods.start_date', '>=', Carbon::now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(payroll_periods.start_date, "%Y-%m") as month, SUM(net_salary) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Department payroll distribution
        $departmentPayroll = Payroll::join('employees', 'payrolls.employee_id', '=', 'employees.id')
            ->join('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->join('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->join('departments', 'positions.department_id', '=', 'departments.id')
            ->join('payroll_periods', 'payrolls.payroll_period_id', '=', 'payroll_periods.id')
            ->where('payroll_periods.status', 'active')
            ->where('employee_positions.status', 'active')
            ->whereNull('employee_positions.end_date')
            ->selectRaw('departments.name, SUM(payrolls.net_salary) as total')
            ->groupBy('departments.id', 'departments.name')
            ->get();

        // Attendance trend (last 30 days)
        $attendanceTrend = Attendance::where('date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(date) as date, COUNT(*) as total, 
                        COUNT(CASE WHEN status = "present" THEN 1 END) as present,
                        COUNT(CASE WHEN status = "absent" THEN 1 END) as absent')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'monthly_payroll' => $monthlyPayroll,
            'department_payroll' => $departmentPayroll,
            'attendance_trend' => $attendanceTrend
        ];
    }

    /**
     * Get payroll statistics for a specific period.
     */
    public function payrollStats(Request $request): JsonResponse
    {
        try {
            $periodId = $request->get('period_id');
            
            if (!$periodId) {
                $period = PayrollPeriod::where('status', 'active')
                    ->orWhere('status', 'open')
                    ->first();
                $periodId = $period?->id;
            }

            if (!$periodId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active payroll period found'
                ], 404);
            }

            $stats = $this->getPayrollSummary(PayrollPeriod::find($periodId));

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Payroll statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving payroll statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payroll statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee statistics.
     */
    public function employeeStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->getEmployeeStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Employee statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving employee statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}