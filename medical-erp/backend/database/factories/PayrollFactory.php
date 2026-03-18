<?php

namespace Database\Factories;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'payroll_number' => 'PAY-' . date('Y') . '-' . date('m') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'month' => now()->month,
            'year' => now()->year,
            'status' => 'draft',
            'total_basic_salary' => 0,
            'total_allowances' => 0,
            'total_additions' => 0,
            'total_deductions' => 0,
            'total_overtime' => 0,
            'total_gosi_employee' => 0,
            'total_gosi_employer' => 0,
            'total_gross_salary' => 0,
            'total_net_salary' => 0,
            'employees_count' => 0,
            'created_by' => User::factory()->state(['user_type' => 'super_admin']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_by' => User::factory()->state(['user_type' => 'super_admin']),
            'approved_at' => now(),
        ]);
    }
}
