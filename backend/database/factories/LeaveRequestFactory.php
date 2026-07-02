<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $startDate = now()->addDays(fake()->numberBetween(7, 30));
        $totalDays = fake()->numberBetween(1, 10);

        return [
            'id' => Str::uuid()->toString(),
            'request_number' => 'LR-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays($totalDays),
            'total_days' => $totalDays,
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'current_approval_step' => 1,
            'total_approval_steps' => 2,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
