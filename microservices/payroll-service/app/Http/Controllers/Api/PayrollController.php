<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display a listing of payrolls with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Payroll::with(['employee:id,first_name,last_name,employee_number', 'payrollPeriod:id,name,start_date,end_date']);

            // Filter by period
            if ($request->has('payroll_period_id')) {
                $query->where('payroll_period_id', $request->payroll_period_id);
            }

            // Filter by employee
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereHas('period', function($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date]);
                });
            }

            // Sort by creation date (newest first)
            $query->orderBy('created_at', 'desc');

            $payrolls = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $payrolls,
                'message' => 'Payrolls retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving payrolls: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payrolls',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate payroll for a specific employee and period.
     */
    public function store(Request $request): JsonResponse
    {
        // Check if payroll already exists first
        $existingPayroll = Payroll::where('employee_id', $request->employee_id)
            ->where('payroll_period_id', $request->payroll_period_id)
            ->first();

        $validator = Validator::make($request->all(), [
            'employee_id' => [
                'required',
                'exists:employees,id',
                function ($attribute, $value, $fail) use ($request, $existingPayroll) {
                    if ($existingPayroll) {
                        $fail('Payroll already exists for this employee and period.');
                    }
                }
            ],
            'payroll_period_id' => 'required|exists:payroll_periods,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);
            $period = PayrollPeriod::findOrFail($request->payroll_period_id);

            $payroll = $this->payrollService->calculatePayroll($employee, $period);

            return response()->json([
                'success' => true,
                'data' => $payroll->load(['details.payrollConcept', 'employee:id,first_name,last_name,employee_number', 'payrollPeriod']),
                'message' => 'Payroll calculated successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error calculating payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payroll with details.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $payroll = Payroll::with([
                'employee:id,first_name,last_name,employee_number,base_salary',
                'employee.currentPosition:id,title',
                'payrollPeriod:id,name,start_date,end_date,status',
                'details.payrollConcept:id,name,code,type,description',
                'concepts.payrollConcept:id,name,code,type,description'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $payroll,
                'message' => 'Payroll retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update payroll status or recalculate if in draft status.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:draft,calculated,approved,paid,cancelled,rechazada',
            'recalculate' => 'sometimes|boolean',
            'worked_days' => 'sometimes|numeric|min:0',
            'worked_hours' => 'sometimes|numeric|min:0',
            'overtime_hours' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payroll = Payroll::findOrFail($id);

            // If recalculate is requested and payroll is in draft status
            if ($request->get('recalculate', false) && $payroll->status === 'draft') {
                $payroll = $this->payrollService->calculatePayroll($payroll->employee, $payroll->payrollPeriod);
            }

            // Update fields if provided
            $updateData = [];
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('worked_days')) {
                $updateData['worked_days'] = $request->worked_days;
            }
            if ($request->has('worked_hours')) {
                $updateData['worked_hours'] = $request->worked_hours;
            }
            if ($request->has('overtime_hours')) {
                $updateData['overtime_hours'] = $request->overtime_hours;
            }
            
            if (!empty($updateData)) {
                $payroll->update($updateData);
            }

            return response()->json([
                'success' => true,
                'data' => $payroll->load(['details.payrollConcept', 'employee:id,first_name,last_name', 'payrollPeriod']),
                'message' => 'Payroll updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete payroll (only if in draft status).
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $payroll = Payroll::findOrFail($id);

            if ($payroll->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete payroll that is not in draft status'
                ], 409);
            }

            $payroll->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payroll deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate payroll for an employee
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'payroll_period_id' => 'required|exists:payroll_periods,id',
            'worked_days' => 'required|integer|min:0|max:31',
            'worked_hours' => 'required|numeric|min:0',
            'overtime_hours' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);
            $payrollPeriod = PayrollPeriod::findOrFail($request->payroll_period_id);

            // Check if payroll already exists for this employee and period
            $existingPayroll = Payroll::where('employee_id', $request->employee_id)
                ->where('payroll_period_id', $request->payroll_period_id)
                ->first();

            if ($existingPayroll && $existingPayroll->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payroll already exists for this employee and period'
                ], 409);
            }

            // Create or update payroll
            $payrollData = [
                'employee_id' => $request->employee_id,
                'payroll_period_id' => $request->payroll_period_id,
                'payroll_number' => 'PAY-' . date('Ym') . '-' . str_pad($employee->id, 6, '0', STR_PAD_LEFT),
                'worked_days' => $request->worked_days,
                'worked_hours' => $request->worked_hours,
                'regular_hours' => $request->worked_hours,
                'overtime_hours' => $request->overtime_hours ?? 0,
                'base_salary' => $employee->base_salary,
                'gross_salary' => 0, // Will be calculated by PayrollService
                'net_salary' => 0, // Will be calculated by PayrollService
                'status' => 'draft'
            ];

            if ($existingPayroll) {
                $existingPayroll->update($payrollData);
                $payroll = $existingPayroll;
            } else {
                $payroll = Payroll::create($payrollData);
            }

            // Calculate payroll using PayrollService
            $calculatedPayroll = $this->payrollService->calculatePayroll($employee, $payrollPeriod);
            
            // Update the payroll with calculated data
            $payroll->update([
                'status' => 'calculated',
                'calculated_at' => now()
            ]);

            // Transform the response to match expected structure
            $responseData = [
                'employee_id' => $calculatedPayroll->employee_id,
                'payroll_period_id' => $calculatedPayroll->payroll_period_id,
                'gross_salary' => $calculatedPayroll->gross_salary,
                'total_deductions' => $calculatedPayroll->total_deductions,
                'net_salary' => $calculatedPayroll->net_salary,
                'concepts' => $calculatedPayroll->details->map(function ($detail) {
                    return [
                        'concept' => $detail->payrollConcept->name,
                        'value' => $detail->amount,
                        'type' => $detail->payrollConcept->type
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Payroll calculated successfully',
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate payroll for all employees in a period.
     */
    public function calculatePeriod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payroll_period_id' => 'required|exists:payroll_periods,id',
            'employee_ids' => 'sometimes|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = PayrollPeriod::findOrFail($request->payroll_period_id);
            $result = $this->payrollService->processPayrollPeriod($period);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Period payroll calculated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating period payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating period payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve payroll period.
     */
    public function approvePeriod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payroll_period_id' => 'required|exists:payroll_periods,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = PayrollPeriod::findOrFail($request->payroll_period_id);
            $result = $this->payrollService->approvePayrollPeriod($period, Auth::id() ?? 1);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Payroll period approved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving payroll period: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error approving payroll period',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve individual payroll.
     */
    public function approve(string $id): JsonResponse
    {
        try {
            $payroll = Payroll::findOrFail($id);

            if ($payroll->status !== 'calculated') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only calculated payrolls can be approved'
                ], 409);
            }

            $payroll->update([
                'status' => 'approved',
                'approved_by' => Auth::id() ?? 1,
                'approved_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $payroll->fresh(),
                'message' => 'Nómina aprobada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error approving payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject individual payroll.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payroll = Payroll::findOrFail($id);

            if (!in_array($payroll->status, ['calculated', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only calculated or approved payrolls can be rejected'
                ], 409);
            }

            $payroll->update([
                'status' => 'rechazada',
                'notes' => $request->rejection_reason,
                'approved_by' => null,
                'approved_at' => null
            ]);

            return response()->json([
                'success' => true,
                'data' => $payroll->fresh(),
                'message' => 'Nómina rechazada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting payroll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting payroll',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payroll summary for a period.
     */
    public function periodSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period_id' => 'required|exists:payroll_periods,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = PayrollPeriod::findOrFail($request->period_id);
            
            $summary = Payroll::where('payroll_period_id', $period->id)
                ->selectRaw('
                    COUNT(*) as total_employees,
                    SUM(gross_salary) as total_gross_salary,
                    SUM(total_earnings) as total_earnings,
                    SUM(total_deductions) as total_deductions,
                    SUM(total_taxes) as total_taxes,
                    SUM(net_salary) as total_net_salary,
                    status
                ')
                ->groupBy('status')
                ->get();

            $overallSummary = Payroll::where('payroll_period_id', $period->id)
                ->selectRaw('
                    COUNT(*) as total_employees,
                    SUM(gross_salary) as total_gross_salary,
                    SUM(total_earnings) as total_earnings,
                    SUM(total_deductions) as total_deductions,
                    SUM(total_taxes) as total_taxes,
                    SUM(net_salary) as total_net_salary
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'summary_by_status' => $summary,
                    'overall_summary' => $overallSummary
                ],
                'message' => 'Payroll summary retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving payroll summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payroll summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
