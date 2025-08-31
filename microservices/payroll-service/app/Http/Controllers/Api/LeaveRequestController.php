<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of leave requests with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LeaveRequest::with(['employee:id,first_name,last_name,employee_code']);

            // Filter by employee
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by leave type
            if ($request->has('leave_type')) {
                $query->where('leave_type', $request->leave_type);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->where('start_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('end_date', '<=', $request->end_date);
            }

            // Filter by year
            if ($request->has('year')) {
                $query->whereYear('start_date', $request->year);
            }

            // Search by employee name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('employee', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            // Sort by created date (default descending)
            $query->orderBy('created_at', 'desc');

            $leaveRequests = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $leaveRequests,
                'message' => 'Leave requests retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving leave requests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving leave requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created leave request.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:vacation,sick,personal,maternity,paternity,bereavement,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'is_paid' => 'required|boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check for overlapping leave requests
            $overlappingLeave = LeaveRequest::where('employee_id', $request->employee_id)
                ->where('status', '!=', 'rejected')
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                          ->orWhere(function($q) use ($request) {
                              $q->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                          });
                })
                ->exists();

            if ($overlappingLeave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee already has a leave request for the specified period'
                ], 409);
            }

            DB::beginTransaction();

            $leaveRequestData = $request->all();
            $leaveRequestData['status'] = 'pending';
            $leaveRequestData['requested_by'] = Auth::id();
            
            // Calculate total days
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $leaveRequestData['total_days'] = $startDate->diffInDays($endDate) + 1;

            $leaveRequest = LeaveRequest::create($leaveRequestData);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $leaveRequest->load('employee:id,first_name,last_name,employee_code'),
                'message' => 'Leave request created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating leave request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified leave request.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $leaveRequest = LeaveRequest::with([
                'employee:id,first_name,last_name,employee_code,department_id,position_id',
                'employee.department:id,name',
                'employee.position:id,name'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $leaveRequest,
                'message' => 'Leave request retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving leave request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified leave request.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|exists:employees,id',
            'leave_type' => 'sometimes|in:vacation,sick,personal,maternity,paternity,bereavement,unpaid',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'sometimes|string|max:500',
            'is_paid' => 'sometimes|boolean',
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
            'notes' => 'nullable|string|max:1000',
            'approved_by' => 'nullable|integer',
            'approved_at' => 'nullable|date',
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            
            // Check if leave request can be modified
            if ($leaveRequest->status === 'approved' && !$request->has('status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify approved leave request'
                ], 409);
            }

            DB::beginTransaction();

            $updateData = $request->all();
            
            // Handle status changes
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'approved':
                        $updateData['approved_by'] = Auth::id();
                        $updateData['approved_at'] = now();
                        $updateData['rejection_reason'] = null;
                        break;
                    case 'rejected':
                        $updateData['approved_by'] = null;
                        $updateData['approved_at'] = null;
                        if (!$request->has('rejection_reason')) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Rejection reason is required when rejecting a leave request'
                            ], 422);
                        }
                        break;
                    case 'cancelled':
                        // Only allow employee or admin to cancel
                        break;
                }
            }
            
            // Recalculate total days if dates are updated
            if ($request->has('start_date') || $request->has('end_date')) {
                $startDate = Carbon::parse($request->start_date ?? $leaveRequest->start_date);
                $endDate = Carbon::parse($request->end_date ?? $leaveRequest->end_date);
                $updateData['total_days'] = $startDate->diffInDays($endDate) + 1;
            }

            $leaveRequest->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $leaveRequest->load('employee:id,first_name,last_name,employee_code'),
                'message' => 'Leave request updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating leave request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified leave request.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);

            // Only allow deletion of pending requests
            if ($leaveRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending leave requests can be deleted'
                ], 409);
            }

            $leaveRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Leave request deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting leave request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a leave request.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leaveRequest = LeaveRequest::findOrFail($id);

            if ($leaveRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending leave requests can be approved'
                ], 409);
            }

            DB::beginTransaction();

            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'notes' => $request->notes ?? $leaveRequest->notes,
                'rejection_reason' => null
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $leaveRequest->load('employee:id,first_name,last_name,employee_code'),
                'message' => 'Leave request approved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving leave request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error approving leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a leave request.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leaveRequest = LeaveRequest::findOrFail($id);

            if ($leaveRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending leave requests can be rejected'
                ], 409);
            }

            DB::beginTransaction();

            $leaveRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'notes' => $request->notes ?? $leaveRequest->notes,
                'approved_by' => null,
                'approved_at' => null
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $leaveRequest->load('employee:id,first_name,last_name,employee_code'),
                'message' => 'Leave request rejected successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting leave request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leave balance for an employee.
     */
    public function leaveBalance(Request $request, string $employeeId): JsonResponse
    {
        $validator = Validator::make(array_merge($request->all(), ['employee_id' => $employeeId]), [
            'employee_id' => 'required|exists:employees,id',
            'year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($employeeId);
            $year = $request->get('year', date('Y'));
            
            $leaveRequests = LeaveRequest::where('employee_id', $employeeId)
                                       ->whereYear('start_date', $year)
                                       ->where('status', 'approved')
                                       ->get();

            $balance = [
                'year' => $year,
                'total_allocated' => 30, // Default annual leave days
                'used' => [
                    'vacation' => $leaveRequests->where('leave_type', 'vacation')->sum('total_days'),
                    'sick' => $leaveRequests->where('leave_type', 'sick')->sum('total_days'),
                    'personal' => $leaveRequests->where('leave_type', 'personal')->sum('total_days'),
                    'other' => $leaveRequests->whereNotIn('leave_type', ['vacation', 'sick', 'personal'])->sum('total_days')
                ],
                'pending' => LeaveRequest::where('employee_id', $employeeId)
                                       ->whereYear('start_date', $year)
                                       ->where('status', 'pending')
                                       ->sum('total_days')
            ];
            
            $balance['total_used'] = array_sum($balance['used']);
            $balance['remaining'] = max(0, $balance['total_allocated'] - $balance['total_used']);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee->only(['id', 'first_name', 'last_name', 'employee_code']),
                    'balance' => $balance
                ],
                'message' => 'Leave balance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving leave balance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving leave balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
