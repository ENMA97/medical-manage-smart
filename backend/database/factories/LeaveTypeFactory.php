<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'code' => 'LT-' . strtoupper(fake()->unique()->bothify('???##')),
            'name' => fake()->randomElement(['Annual Leave', 'Sick Leave', 'Emergency Leave', 'Maternity Leave']),
            'name_ar' => 'إجازة',
            'category' => fake()->randomElement(['annual', 'sick', 'emergency', 'maternity']),
            'default_days_per_year' => 30,
            'max_days_per_request' => 30,
            'min_days_per_request' => 1,
            'is_paid' => true,
            'pay_percentage' => 100,
            'requires_attachment' => false,
            'requires_substitute' => false,
            'advance_notice_days' => 7,
            'carries_forward' => false,
            'max_carry_forward_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
