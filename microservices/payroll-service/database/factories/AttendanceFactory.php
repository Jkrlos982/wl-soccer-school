<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');
        $checkInTime = Carbon::parse($date)->setTime(8, 0, 0); // 8:00 AM
        $checkOutTime = Carbon::parse($date)->setTime(17, 0, 0); // 5:00 PM
        $totalHours = 8;
        $overtimeHours = 0;
        
        return [
            'employee_id' => Employee::factory(),
            'date' => $date,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'break_start_time' => Carbon::parse($date)->setTime(12, 0, 0), // 12:00 PM
            'break_end_time' => Carbon::parse($date)->setTime(13, 0, 0), // 1:00 PM
            'worked_hours' => $totalHours,
            'overtime_hours' => $overtimeHours,
            'break_hours' => 1.0,
            'status' => 'present',
            'shift_type' => 'morning',
            'notes' => null,
            'is_overtime_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Indicate that the attendance is for overtime work.
     */
    public function overtime(): static
    {
        return $this->state(function (array $attributes) {
            $checkOutTime = Carbon::parse($attributes['date'])->setTime(19, 0, 0); // 7:00 PM
            return [
                'check_out_time' => $checkOutTime,
                'worked_hours' => 10,
                'overtime_hours' => 2,
            ];
        });
    }

    /**
     * Indicate that the employee was late.
     */
    public function late(): static
    {
        return $this->state(function (array $attributes) {
            $checkInTime = Carbon::parse($attributes['date'])->setTime(9, 30, 0); // 9:30 AM
            return [
                'check_in_time' => $checkInTime,
                'status' => 'late',
            ];
        });
    }

    /**
     * Indicate that the employee was absent.
     */
    public function absent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'check_in_time' => null,
                'check_out_time' => null,
                'break_start_time' => null,
                'break_end_time' => null,
                'worked_hours' => 0,
                'overtime_hours' => 0,
                'break_hours' => 0,
                'status' => 'absent',
            ];
        });
    }

    /**
     * Indicate that the employee was on leave.
     */
    public function onLeave(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'check_in_time' => null,
                'check_out_time' => null,
                'break_start_time' => null,
                'break_end_time' => null,
                'worked_hours' => 0,
                'overtime_hours' => 0,
                'break_hours' => 0,
                'status' => 'leave',
                'notes' => 'On leave',
            ];
        });
    }
}