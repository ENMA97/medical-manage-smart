<?php

namespace Database\Factories\Leave;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        $entitledDays = $this->faker->numberBetween(15, 30);
        $usedDays = $this->faker->numberBetween(0, $entitledDays);

        return [
            'employee_id' => $this->faker->uuid(),
            'leave_type_id' => LeaveType::factory(),
            'year' => date('Y'),
            'entitled_days' => $entitledDays,
            'used_days' => $usedDays,
            'pending_days' => 0,
            'remaining_days' => $entitledDays - $usedDays,
            'carried_over_days' => 0,
        ];
    }

    public function forEmployee(string $employeeId): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => $employeeId,
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
        ]);
    }

    public function withCarryOver(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'carried_over_days' => $days,
            'entitled_days' => $attributes['entitled_days'] + $days,
            'remaining_days' => $attributes['remaining_days'] + $days,
        ]);
    }

    public function fullyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_days' => $attributes['entitled_days'],
            'remaining_days' => 0,
        ]);
    }
}
