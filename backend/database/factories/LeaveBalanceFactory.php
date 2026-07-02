<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'year' => now()->year,
            'total_entitled' => 30,
            'carried_forward' => 0,
            'additional_granted' => 0,
            'used' => 0,
            'pending' => 0,
            'remaining' => 30,
        ];
    }
}
