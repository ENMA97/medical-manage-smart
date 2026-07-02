<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $basicSalary = fake()->numberBetween(5000, 20000);
        $housing = round($basicSalary * 0.25, 2);
        $transport = 500;
        $food = 300;

        return [
            'id' => Str::uuid()->toString(),
            'employee_id' => Employee::factory(),
            'contract_number' => 'CNT-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'contract_type' => fake()->randomElement(['full_time', 'part_time', 'temporary']),
            'status' => 'active',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->addMonths(6),
            'duration_months' => 12,
            'probation_days' => 90,
            'basic_salary' => $basicSalary,
            'housing_allowance' => $housing,
            'transport_allowance' => $transport,
            'food_allowance' => $food,
            'phone_allowance' => 0,
            'other_allowances' => 0,
            'total_salary' => $basicSalary + $housing + $transport + $food,
            'annual_leave_days' => 30,
            'sick_leave_days' => 30,
            'notice_period_days' => 30,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);
    }
}
