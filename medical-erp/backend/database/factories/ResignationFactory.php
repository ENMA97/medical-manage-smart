<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Resignation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ResignationFactory extends Factory
{
    protected $model = Resignation::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(['resignation', 'termination', 'end_of_contract']),
            'request_date' => now(),
            'last_working_day' => now()->addMonth(),
            'notice_period_days' => 30,
            'reason' => fake()->sentence(),
            'reason_ar' => 'سبب الاستقالة',
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }
}
