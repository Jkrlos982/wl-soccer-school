<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendance records with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Attendance::with(['employee:id,first_name,last_name,employee_code']);

            // Filter by employee
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->where('date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('date', '<=', $request->end_date);
            }

            // Filter by specific date
            if ($request->has('date')) {
                $query->whereDate('date', $request->date);
            }

            // Filter by attendance type
            if ($request->has('type')) {
                $query->where('type', $request->type);
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

            // Sort by date (default descending)
            $query->orderBy('date', 'desc')->orderBy('check_in', 'desc');

            $attendances = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $attendances,
                'message' => 'Attendance records retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving attendance records: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving attendance records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created attendance record.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s|after:check_in',
            'break_start' => 'nullable|date_format:H:i:s',
            'break_end' => 'nullable|date_format:H:i:s|after:break_start',
            'type' => 'required|in:regular,overtime,holiday,weekend',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if attendance already exists for this employee and date
            $existingAttendance = Attendance::where('employee_id', $request->employee_id)
                                           ->whereDate('date', $request->date)
                                           ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record already exists for this employee and date'
                ], 409);
            }

            DB::beginTransaction();

            $attendanceData = $request->all();
            
            // Calculate worked hours if check_out is provided
            if ($request->check_out) {
                $attendanceData = $this->calculateWorkedHours($attendanceData);
            }

            $attendance = Attendance::create($attendanceData);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $attendance->load('employee:id,first_name,last_name,employee_code'),
                'message' => 'Attendance record created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating attendance record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified attendance record.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $attendance = Attendance::with([
                'employee:id,first_name,last_name,employee_code,department_id,position_id',
                'employee.department:id,name',
                'employee.position:id,name'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Attendance record retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified attendance record.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|exists:employees,id',
            'date' => 'sometimes|date',
            'check_in' => 'sometimes|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s|after:check_in',
            'break_start' => 'nullable|date_format:H:i:s',
            'break_end' => 'nullable|date_format:H:i:s|after:break_start',
            'type' => 'sometimes|in:regular,overtime,holiday,weekend',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attendance = Attendance::findOrFail($id);
            
            DB::beginTransaction();

            $attendanceData = $request->all();
            
            // Recalculate worked hours if times are updated
            if ($request->has('check_in') || $request->has('check_out') || 
                $request->has('break_start') || $request->has('break_end')) {
                
                // Merge with existing data for calculation
                $mergedData = array_merge($attendance->toArray(), $attendanceData);
                $attendanceData = $this->calculateWorkedHours($mergedData);
            }

            $attendance->update($attendanceData);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $attendance->load('employee:id,first_name,last_name,employee_code'),
                'message' => 'Attendance record updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating attendance record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified attendance record.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $attendance = Attendance::findOrFail($id);

            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting attendance record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance summary for an employee within a date range.
     */
    public function employeeSummary(Request $request, string $employeeId): JsonResponse
    {
        $validator = Validator::make(array_merge($request->all(), ['employee_id' => $employeeId]), [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
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
            
            $attendances = Attendance::where('employee_id', $employeeId)
                                   ->whereBetween('date', [$request->start_date, $request->end_date])
                                   ->get();

            $summary = [
                'total_days' => $attendances->count(),
                'total_worked_hours' => $attendances->sum('worked_hours'),
                'total_overtime_hours' => $attendances->sum('overtime_hours'),
                'total_break_hours' => $attendances->sum('break_hours'),
                'average_daily_hours' => $attendances->count() > 0 ? 
                    round($attendances->sum('worked_hours') / $attendances->count(), 2) : 0,
                'by_type' => $attendances->groupBy('type')->map(function($group) {
                    return [
                        'count' => $group->count(),
                        'total_hours' => $group->sum('worked_hours')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => $employee->only(['id', 'first_name', 'last_name', 'employee_code']),
                    'period' => [
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date
                    ],
                    'summary' => $summary,
                    'attendances' => $attendances
                ],
                'message' => 'Employee attendance summary retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving employee attendance summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving attendance summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clock in/out functionality.
     */
    public function clockInOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'action' => 'required|in:clock_in,clock_out,break_start,break_end',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $today = Carbon::today()->toDateString();
            $currentTime = Carbon::now()->format('H:i:s');
            
            $attendance = Attendance::where('employee_id', $request->employee_id)
                                  ->whereDate('date', $today)
                                  ->first();

            DB::beginTransaction();

            switch ($request->action) {
                case 'clock_in':
                    if ($attendance) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Employee already clocked in today'
                        ], 409);
                    }
                    
                    $attendance = Attendance::create([
                        'employee_id' => $request->employee_id,
                        'date' => $today,
                        'check_in' => $currentTime,
                        'type' => 'regular',
                        'notes' => $request->notes
                    ]);
                    break;

                case 'clock_out':
                    if (!$attendance || $attendance->check_out) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid clock out attempt'
                        ], 409);
                    }
                    
                    $attendanceData = array_merge($attendance->toArray(), [
                        'check_out' => $currentTime,
                        'notes' => $request->notes ?? $attendance->notes
                    ]);
                    
                    $calculatedData = $this->calculateWorkedHours($attendanceData);
                    $attendance->update($calculatedData);
                    break;

                case 'break_start':
                    if (!$attendance || $attendance->break_start) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid break start attempt'
                        ], 409);
                    }
                    
                    $attendance->update(['break_start' => $currentTime]);
                    break;

                case 'break_end':
                    if (!$attendance || !$attendance->break_start || $attendance->break_end) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid break end attempt'
                        ], 409);
                    }
                    
                    $attendanceData = array_merge($attendance->toArray(), [
                        'break_end' => $currentTime
                    ]);
                    
                    if ($attendance->check_out) {
                        $calculatedData = $this->calculateWorkedHours($attendanceData);
                        $attendance->update($calculatedData);
                    } else {
                        $attendance->update(['break_end' => $currentTime]);
                    }
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $attendance->load('employee:id,first_name,last_name,employee_code'),
                'message' => ucfirst(str_replace('_', ' ', $request->action)) . ' recorded successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing clock in/out: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing clock in/out',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate worked hours, overtime, and break hours.
     */
    private function calculateWorkedHours(array $attendanceData): array
    {
        if (!isset($attendanceData['check_in']) || !isset($attendanceData['check_out'])) {
            return $attendanceData;
        }

        $checkIn = Carbon::createFromFormat('H:i:s', $attendanceData['check_in']);
        $checkOut = Carbon::createFromFormat('H:i:s', $attendanceData['check_out']);
        
        $totalMinutes = $checkOut->diffInMinutes($checkIn);
        
        // Calculate break time
        $breakMinutes = 0;
        if (isset($attendanceData['break_start']) && isset($attendanceData['break_end'])) {
            $breakStart = Carbon::createFromFormat('H:i:s', $attendanceData['break_start']);
            $breakEnd = Carbon::createFromFormat('H:i:s', $attendanceData['break_end']);
            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
        }
        
        $workedMinutes = $totalMinutes - $breakMinutes;
        $workedHours = round($workedMinutes / 60, 2);
        
        // Calculate overtime (assuming 8 hours is standard)
        $standardHours = 8;
        $overtimeHours = max(0, $workedHours - $standardHours);
        
        $attendanceData['worked_hours'] = $workedHours;
        $attendanceData['overtime_hours'] = $overtimeHours;
        $attendanceData['break_hours'] = round($breakMinutes / 60, 2);
        
        return $attendanceData;
    }
}
