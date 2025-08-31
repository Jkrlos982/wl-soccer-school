<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Record employee check-in
     */
    public function checkIn(Employee $employee, ?Carbon $checkInTime = null): Attendance
    {
        $checkInTime = $checkInTime ?? now();
        $date = $checkInTime->toDateString();
        
        // Check if attendance already exists for today
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->first();

        if ($attendance && $attendance->check_in_time) {
            throw new \Exception('Employee has already checked in today');
        }

        if (!$attendance) {
            $attendance = new Attendance([
                'employee_id' => $employee->id,
                'attendance_date' => $date
            ]);
        }

        $attendance->check_in_time = $checkInTime->toTimeString();
        $attendance->status = $this->determineStatus($checkInTime);
        $attendance->save();

        Log::info("Employee {$employee->id} checked in at {$checkInTime}");
        
        return $attendance;
    }

    /**
     * Record employee check-out
     */
    public function checkOut(Employee $employee, ?Carbon $checkOutTime = null): Attendance
    {
        $checkOutTime = $checkOutTime ?? now();
        $date = $checkOutTime->toDateString();
        
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            throw new \Exception('Employee must check in before checking out');
        }

        if ($attendance->check_out_time) {
            throw new \Exception('Employee has already checked out today');
        }

        $attendance->check_out_time = $checkOutTime->toTimeString();
        $attendance = $this->calculateWorkingHours($attendance);
        $attendance->save();

        Log::info("Employee {$employee->id} checked out at {$checkOutTime}");
        
        return $attendance;
    }

    /**
     * Mark employee as absent
     */
    public function markAbsent(Employee $employee, Carbon $date, ?string $reason = null): Attendance
    {
        $dateString = $date->toDateString();
        
        $attendance = Attendance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $dateString
            ],
            [
                'status' => 'absent',
                'notes' => $reason,
                'total_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0
            ]
        );

        if ($attendance->wasRecentlyCreated) {
            Log::info("Employee {$employee->id} marked as absent for {$dateString}");
        }
        
        return $attendance;
    }

    /**
     * Mark employee as on leave
     */
    public function markOnLeave(Employee $employee, Carbon $date, string $leaveType): Attendance
    {
        $dateString = $date->toDateString();
        
        $attendance = Attendance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $dateString
            ],
            [
                'status' => 'leave',
                'notes' => "On {$leaveType} leave",
                'total_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0
            ]
        );

        Log::info("Employee {$employee->id} marked on {$leaveType} leave for {$dateString}");
        
        return $attendance;
    }

    /**
     * Update attendance record
     */
    public function updateAttendance(Attendance $attendance, array $data): Attendance
    {
        $attendance->update([
            'check_in_time' => $data['check_in_time'] ?? $attendance->check_in_time,
            'check_out_time' => $data['check_out_time'] ?? $attendance->check_out_time,
            'status' => $data['status'] ?? $attendance->status,
            'notes' => $data['notes'] ?? $attendance->notes
        ]);

        // Recalculate hours if times were updated
        if (isset($data['check_in_time']) || isset($data['check_out_time'])) {
            $attendance = $this->calculateWorkingHours($attendance);
            $attendance->save();
        }

        Log::info("Attendance updated for employee {$attendance->employee_id} on {$attendance->attendance_date}");
        
        return $attendance;
    }

    /**
     * Calculate working hours for an attendance record
     */
    private function calculateWorkingHours(Attendance $attendance): Attendance
    {
        if (!$attendance->check_in_time || !$attendance->check_out_time) {
            return $attendance;
        }

        $checkIn = Carbon::parse($attendance->attendance_date . ' ' . $attendance->check_in_time);
        $checkOut = Carbon::parse($attendance->attendance_date . ' ' . $attendance->check_out_time);
        
        // Handle overnight shifts
        if ($checkOut->lt($checkIn)) {
            $checkOut->addDay();
        }

        $totalMinutes = $checkOut->diffInMinutes($checkIn);
        
        // Subtract lunch break (assuming 1 hour lunch break for shifts > 6 hours)
        $lunchBreakMinutes = $totalMinutes > 360 ? 60 : 0;
        $workingMinutes = $totalMinutes - $lunchBreakMinutes;
        
        $totalHours = round($workingMinutes / 60, 2);
        $standardHours = config('payroll.standard_working_hours', 8);
        
        $regularHours = min($totalHours, $standardHours);
        $overtimeHours = max(0, $totalHours - $standardHours);

        $attendance->total_hours = $totalHours;
        $attendance->regular_hours = $regularHours;
        $attendance->overtime_hours = $overtimeHours;
        $attendance->break_time = $lunchBreakMinutes;

        return $attendance;
    }

    /**
     * Determine attendance status based on check-in time
     */
    private function determineStatus(Carbon $checkInTime): string
    {
        $standardStartTime = config('payroll.standard_start_time', '08:00');
        $lateThreshold = config('payroll.late_threshold_minutes', 15);
        
        $standardStart = Carbon::parse($checkInTime->toDateString() . ' ' . $standardStartTime);
        $lateThresholdTime = $standardStart->copy()->addMinutes($lateThreshold);
        
        if ($checkInTime->lte($standardStart)) {
            return 'present';
        } elseif ($checkInTime->lte($lateThresholdTime)) {
            return 'late';
        } else {
            return 'very_late';
        }
    }

    /**
     * Get attendance summary for an employee in a period
     */
    public function getAttendanceSummary(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $workingDays = $this->getWorkingDays($startDate, $endDate);
        
        $present = $attendance->whereIn('status', ['present', 'late', 'very_late'])->count();
        $absent = $attendance->where('status', 'absent')->count();
        $onLeave = $attendance->where('status', 'leave')->count();
        $late = $attendance->whereIn('status', ['late', 'very_late'])->count();
        
        $totalHours = $attendance->sum('total_hours');
        $regularHours = $attendance->sum('regular_hours');
        $overtimeHours = $attendance->sum('overtime_hours');
        
        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $totalDays,
                'working_days' => $workingDays
            ],
            'attendance' => [
                'present_days' => $present,
                'absent_days' => $absent,
                'leave_days' => $onLeave,
                'late_days' => $late,
                'attendance_rate' => $workingDays > 0 ? round(($present / $workingDays) * 100, 2) : 0
            ],
            'hours' => [
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'average_daily_hours' => $present > 0 ? round($totalHours / $present, 2) : 0
            ]
        ];
    }

    /**
     * Get attendance report for a period
     */
    public function getAttendanceReport(Carbon $startDate, Carbon $endDate, ?int $departmentId = null): array
    {
        $query = Attendance::with(['employee.currentPosition.department'])
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($departmentId) {
            $query->whereHas('employee.currentPosition', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $attendance = $query->get();
        $employees = $attendance->pluck('employee')->unique('id');
        
        $report = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'working_days' => $this->getWorkingDays($startDate, $endDate)
            ],
            'summary' => [
                'total_employees' => $employees->count(),
                'total_present' => $attendance->whereIn('status', ['present', 'late', 'very_late'])->count(),
                'total_absent' => $attendance->where('status', 'absent')->count(),
                'total_on_leave' => $attendance->where('status', 'leave')->count(),
                'total_late' => $attendance->whereIn('status', ['late', 'very_late'])->count(),
                'total_hours' => $attendance->sum('total_hours'),
                'total_overtime' => $attendance->sum('overtime_hours')
            ],
            'by_department' => [],
            'employees' => []
        ];

        // Group by department
        $byDepartment = $attendance->groupBy('employee.currentPosition.department.name');
        foreach ($byDepartment as $departmentName => $deptAttendance) {
            $report['by_department'][$departmentName] = [
                'present' => $deptAttendance->whereIn('status', ['present', 'late', 'very_late'])->count(),
                'absent' => $deptAttendance->where('status', 'absent')->count(),
                'on_leave' => $deptAttendance->where('status', 'leave')->count(),
                'total_hours' => $deptAttendance->sum('total_hours'),
                'overtime_hours' => $deptAttendance->sum('overtime_hours')
            ];
        }

        // Individual employee summaries
        foreach ($employees as $employee) {
            $employeeAttendance = $attendance->where('employee_id', $employee->id);
            $report['employees'][] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'department' => $employee->currentPosition->department->name ?? 'N/A',
                'present_days' => $employeeAttendance->whereIn('status', ['present', 'late', 'very_late'])->count(),
                'absent_days' => $employeeAttendance->where('status', 'absent')->count(),
                'leave_days' => $employeeAttendance->where('status', 'leave')->count(),
                'late_days' => $employeeAttendance->whereIn('status', ['late', 'very_late'])->count(),
                'total_hours' => $employeeAttendance->sum('total_hours'),
                'overtime_hours' => $employeeAttendance->sum('overtime_hours')
            ];
        }

        return $report;
    }

    /**
     * Calculate working days between two dates (excluding weekends)
     */
    private function getWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }

    /**
     * Generate attendance for employees who didn't check in
     */
    public function generateMissingAttendance(Carbon $date): int
    {
        $dateString = $date->toDateString();
        $activeEmployees = Employee::where('status', 'active')->get();
        $generated = 0;
        
        foreach ($activeEmployees as $employee) {
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->where('attendance_date', $dateString)
                ->exists();
                
            if (!$existingAttendance && $date->isWeekday()) {
                $this->markAbsent($employee, $date, 'No check-in recorded');
                $generated++;
            }
        }
        
        Log::info("Generated {$generated} missing attendance records for {$dateString}");
        
        return $generated;
    }

    /**
     * Get employees currently checked in
     */
    public function getCurrentlyCheckedIn(): \Illuminate\Database\Eloquent\Collection
    {
        $today = now()->toDateString();
        
        return Attendance::with('employee')
            ->where('attendance_date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->get();
    }

    /**
     * Get late arrivals for today
     */
    public function getTodayLateArrivals(): \Illuminate\Database\Eloquent\Collection
    {
        $today = now()->toDateString();
        
        return Attendance::with('employee')
            ->where('attendance_date', $today)
            ->whereIn('status', ['late', 'very_late'])
            ->get();
    }
}