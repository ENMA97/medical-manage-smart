<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    /**
     * عدّاد تسلسلي يضمن تاريخاً مختلفاً لكل سجل (قيد فريد لكل موظف/يوم)
     */
    private static int $daySequence = 0;

    public function definition(): array
    {
        $date = now()->subDays(self::$daySequence++)->toDateString();

        return [
            'employee_id' => Employee::factory(),
            'attendance_date' => $date,
            'check_in_time' => "{$date} 08:05:00",
            'check_out_time' => "{$date} 16:00:00",
            'status' => 'present',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'worked_minutes' => 475,
            'source' => 'manual',
        ];
    }

    public function absent(): static
    {
        return $this->state(fn () => [
            'check_in_time' => null,
            'check_out_time' => null,
            'status' => 'absent',
            'worked_minutes' => 0,
        ]);
    }
}
