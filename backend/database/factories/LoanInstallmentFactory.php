<?php

namespace Database\Factories;

use App\Models\EmployeeLoan;
use App\Models\LoanInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LoanInstallmentFactory extends Factory
{
    protected $model = LoanInstallment::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'loan_id' => EmployeeLoan::factory(),
            'installment_number' => fake()->numberBetween(1, 24),
            'amount' => fake()->numberBetween(500, 2000),
            'remaining_after' => fake()->numberBetween(0, 50000),
            'due_date' => now()->addMonths(fake()->numberBetween(1, 12)),
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'paid_date' => now(),
        ]);
    }
}
