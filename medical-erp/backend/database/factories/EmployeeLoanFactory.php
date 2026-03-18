<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmployeeLoanFactory extends Factory
{
    protected $model = EmployeeLoan::class;

    public function definition(): array
    {
        $loanAmount = fake()->numberBetween(5000, 50000);
        $monthlyDeduction = fake()->numberBetween(500, 2000);
        $totalInstallments = (int) ceil($loanAmount / $monthlyDeduction);

        return [
            'id' => Str::uuid()->toString(),
            'employee_id' => Employee::factory(),
            'loan_number' => 'LOAN-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'loan_amount' => $loanAmount,
            'monthly_deduction' => $monthlyDeduction,
            'remaining_amount' => $loanAmount,
            'total_installments' => $totalInstallments,
            'paid_installments' => 0,
            'remaining_installments' => $totalInstallments,
            'start_date' => now()->addMonth(),
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
