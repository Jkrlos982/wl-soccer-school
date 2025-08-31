<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PayrollPeriodController;
use App\Http\Controllers\Api\PayrollConceptController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\PayrollReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'payroll-service',
        'timestamp' => now()->toISOString()
    ]);
});

// Authentication routes (if needed)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API version 1 routes
Route::prefix('v1')->group(function () {
    
    // Employee management routes
    Route::apiResource('employees', EmployeeController::class);
    Route::prefix('employees')->group(function () {
        Route::get('{employee}/payrolls', [EmployeeController::class, 'payrolls']);
        Route::get('{employee}/attendance', [EmployeeController::class, 'attendance']);
        Route::get('{employee}/leave-requests', [EmployeeController::class, 'leaveRequests']);
        Route::post('{employee}/activate', [EmployeeController::class, 'activate']);
        Route::post('{employee}/deactivate', [EmployeeController::class, 'deactivate']);
        Route::get('{employee}/salary-history', [EmployeeController::class, 'salaryHistory']);
        Route::post('{employee}/update-salary', [EmployeeController::class, 'updateSalary']);
    });
    
    // Department management routes
    Route::apiResource('departments', DepartmentController::class);
    Route::prefix('departments')->group(function () {
        Route::get('{department}/employees', [DepartmentController::class, 'employees']);
        Route::get('{department}/payroll-summary', [DepartmentController::class, 'payrollSummary']);
        Route::post('{department}/activate', [DepartmentController::class, 'activate']);
        Route::post('{department}/deactivate', [DepartmentController::class, 'deactivate']);
        Route::get('tree', [DepartmentController::class, 'tree']);
    });
    
    // Position management routes
    Route::apiResource('positions', PositionController::class);
    Route::prefix('positions')->group(function () {
        Route::get('{position}/employees', [PositionController::class, 'employees']);
        Route::post('{position}/activate', [PositionController::class, 'activate']);
        Route::post('{position}/deactivate', [PositionController::class, 'deactivate']);
    });
    
    // Payroll management routes
    Route::apiResource('payrolls', PayrollController::class);
    Route::prefix('payrolls')->group(function () {
        Route::post('calculate', [PayrollController::class, 'calculate']);
        Route::post('bulk-calculate', [PayrollController::class, 'bulkCalculate']);
        Route::post('{payroll}/approve', [PayrollController::class, 'approve']);
        Route::post('{payroll}/reject', [PayrollController::class, 'reject']);
        Route::post('{payroll}/process', [PayrollController::class, 'process']);
        Route::post('{payroll}/reverse', [PayrollController::class, 'reverse']);
        Route::get('{payroll}/details', [PayrollController::class, 'details']);
        Route::get('period/{period}', [PayrollController::class, 'byPeriod']);
        Route::get('employee/{employee}', [PayrollController::class, 'byEmployee']);
        Route::get('department/{department}', [PayrollController::class, 'byDepartment']);
    });
    
    // Payroll period management routes
    Route::prefix('payroll-periods')->group(function () {
        Route::get('current', [PayrollPeriodController::class, 'current']);
        Route::get('active', [PayrollPeriodController::class, 'active']);
        Route::post('{payroll_period}/open', [PayrollPeriodController::class, 'open']);
        Route::post('{payroll_period}/close', [PayrollPeriodController::class, 'close']);
        Route::post('{payroll_period}/reopen', [PayrollPeriodController::class, 'reopen']);
        Route::get('{payroll_period}/payrolls', [PayrollPeriodController::class, 'payrolls']);
        Route::get('{payroll_period}/summary', [PayrollPeriodController::class, 'summary']);
    });
    Route::apiResource('payroll-periods', PayrollPeriodController::class);
    
    // Payroll concept management routes
    Route::apiResource('payroll-concepts', PayrollConceptController::class);
    Route::prefix('payroll-concepts')->group(function () {
        Route::post('{concept}/activate', [PayrollConceptController::class, 'activate']);
        Route::post('{concept}/deactivate', [PayrollConceptController::class, 'deactivate']);
        Route::post('validate-formula', [PayrollConceptController::class, 'validateFormula']);
        Route::get('by-type/{type}', [PayrollConceptController::class, 'byType']);
        Route::get('active', [PayrollConceptController::class, 'active']);
    });
    
    // Attendance management routes
    Route::apiResource('attendance', AttendanceController::class);
    Route::prefix('attendance')->group(function () {
        Route::post('check-in', [AttendanceController::class, 'checkIn']);
        Route::post('check-out', [AttendanceController::class, 'checkOut']);
        Route::post('break-start', [AttendanceController::class, 'breakStart']);
        Route::post('break-end', [AttendanceController::class, 'breakEnd']);
        Route::get('employee/{employee}', [AttendanceController::class, 'byEmployee']);
        Route::get('date/{date}', [AttendanceController::class, 'byDate']);
        Route::get('period/{startDate}/{endDate}', [AttendanceController::class, 'byPeriod']);
        Route::get('department/{department}', [AttendanceController::class, 'byDepartment']);
        Route::get('{attendance}/summary', [AttendanceController::class, 'summary']);
        Route::post('bulk-import', [AttendanceController::class, 'bulkImport']);
    });
    
    // Leave request management routes
    Route::apiResource('leave-requests', LeaveRequestController::class);
    Route::prefix('leave-requests')->group(function () {
        Route::post('{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
        Route::post('{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);
        Route::get('employee/{employee}', [LeaveRequestController::class, 'byEmployee']);
        Route::get('pending', [LeaveRequestController::class, 'pending']);
        Route::get('approved', [LeaveRequestController::class, 'approved']);
        Route::get('by-type/{type}', [LeaveRequestController::class, 'byType']);
        Route::get('by-status/{status}', [LeaveRequestController::class, 'byStatus']);
        Route::get('{leaveRequest}/balance', [LeaveRequestController::class, 'balance']);
    });
    
    // Payroll reports routes
    Route::prefix('reports')->group(function () {
        Route::get('payroll-summary', [PayrollReportController::class, 'payrollSummary']);
        Route::get('detailed-payroll', [PayrollReportController::class, 'detailedPayroll']);
        Route::get('tax-report', [PayrollReportController::class, 'taxReport']);
        Route::get('attendance-report', [PayrollReportController::class, 'attendanceReport']);
        Route::get('leave-report', [PayrollReportController::class, 'leaveReport']);
        Route::get('department-costs', [PayrollReportController::class, 'departmentCosts']);
        Route::get('employee-summary', [PayrollReportController::class, 'employeeSummary']);
        
        // PDF and Excel export routes
        Route::get('payroll-summary/pdf', [PayrollReportController::class, 'payrollSummaryPdf']);
        Route::get('payroll-summary/excel', [PayrollReportController::class, 'payrollSummaryExcel']);
        Route::get('detailed-payroll/pdf', [PayrollReportController::class, 'detailedPayrollPdf']);
        Route::get('detailed-payroll/excel', [PayrollReportController::class, 'detailedPayrollExcel']);
        Route::get('tax-report/pdf', [PayrollReportController::class, 'taxReportPdf']);
        Route::get('tax-report/excel', [PayrollReportController::class, 'taxReportExcel']);
        Route::get('attendance-report/pdf', [PayrollReportController::class, 'attendanceReportPdf']);
        Route::get('attendance-report/excel', [PayrollReportController::class, 'attendanceReportExcel']);
        Route::get('leave-report/pdf', [PayrollReportController::class, 'leaveReportPdf']);
        Route::get('leave-report/excel', [PayrollReportController::class, 'leaveReportExcel']);
    });
    
    // Dashboard and analytics routes
    Route::prefix('dashboard')->group(function () {
        Route::get('overview', [DashboardController::class, 'overview']);
        Route::get('payroll-metrics', [DashboardController::class, 'payrollMetrics']);
        Route::get('attendance-metrics', [DashboardController::class, 'attendanceMetrics']);
        Route::get('leave-metrics', [DashboardController::class, 'leaveMetrics']);
        Route::get('department-metrics', [DashboardController::class, 'departmentMetrics']);
        Route::get('cost-analysis', [DashboardController::class, 'costAnalysis']);
        Route::get('trends', [DashboardController::class, 'trends']);
        Route::get('alerts', [DashboardController::class, 'alerts']);
    });
    
    // Bulk operations routes
    Route::prefix('bulk')->group(function () {
        Route::post('employees/import', [EmployeeController::class, 'bulkImport']);
        Route::post('employees/export', [EmployeeController::class, 'bulkExport']);
        Route::post('payrolls/calculate', [PayrollController::class, 'bulkCalculate']);
        Route::post('payrolls/approve', [PayrollController::class, 'bulkApprove']);
        Route::post('payrolls/process', [PayrollController::class, 'bulkProcess']);
        Route::post('attendance/import', [AttendanceController::class, 'bulkImport']);
        Route::post('leave-requests/approve', [LeaveRequestController::class, 'bulkApprove']);
        Route::post('leave-requests/reject', [LeaveRequestController::class, 'bulkReject']);
    });
    
    // Configuration and settings routes
    Route::prefix('config')->group(function () {
        Route::get('payroll-concepts', [PayrollConceptController::class, 'config']);
        Route::get('tax-rates', function () {
            return response()->json([
                'income_tax_rates' => [
                    ['min' => 0, 'max' => 1000000, 'rate' => 0],
                    ['min' => 1000001, 'max' => 2000000, 'rate' => 0.05],
                    ['min' => 2000001, 'max' => 3000000, 'rate' => 0.10],
                    ['min' => 3000001, 'max' => 5000000, 'rate' => 0.15],
                    ['min' => 5000001, 'max' => null, 'rate' => 0.20]
                ],
                'social_security_rates' => [
                    'health' => 0.04,
                    'pension' => 0.04,
                    'arl' => 0.00522
                ]
            ]);
        });
        Route::get('leave-types', function () {
            return response()->json([
                'vacation' => ['max_days' => 30, 'requires_approval' => true],
                'sick' => ['max_days' => 90, 'requires_medical_certificate' => true],
                'personal' => ['max_days' => 5, 'requires_approval' => true],
                'maternity' => ['max_days' => 126, 'requires_approval' => false],
                'paternity' => ['max_days' => 14, 'requires_approval' => false],
                'bereavement' => ['max_days' => 5, 'requires_approval' => false],
                'emergency' => ['max_days' => 3, 'requires_approval' => true],
                'unpaid' => ['max_days' => 365, 'requires_approval' => true]
            ]);
        });
    });
    
    // Search and filtering routes
    Route::prefix('search')->group(function () {
        Route::get('employees', [EmployeeController::class, 'search']);
        Route::get('departments', [DepartmentController::class, 'search']);
        Route::get('payrolls', [PayrollController::class, 'search']);
        Route::get('attendance', [AttendanceController::class, 'search']);
        Route::get('leave-requests', [LeaveRequestController::class, 'search']);
    });
});

// Webhook routes (for external integrations)
Route::prefix('webhooks')->group(function () {
    Route::post('attendance/biometric', [AttendanceController::class, 'biometricWebhook']);
    Route::post('hr-system/employee-update', [EmployeeController::class, 'hrSystemWebhook']);
    Route::post('accounting/payroll-processed', [PayrollController::class, 'accountingWebhook']);
});

// Public routes (no authentication required)
Route::prefix('public')->group(function () {
    Route::get('departments/tree', [DepartmentController::class, 'publicTree']);
    Route::get('positions/list', [PositionController::class, 'publicList']);
});