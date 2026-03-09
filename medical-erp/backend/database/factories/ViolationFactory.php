<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ViolationFactory extends Factory
{
    protected $model = Violation::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'violation_number' => 'VIO-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'employee_id' => Employee::factory(),
            'violation_type_id' => ViolationType::factory(),
            'violation_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'location' => fake()->randomElement(['المكتب', 'المختبر', 'غرفة العمليات', 'الاستقبال']),
            'description' => 'وصف المخالفة التجريبية',
            'description_ar' => 'وصف المخالفة التجريبية',
            'occurrence_number' => 1,
            'status' => 'reported',
            'reported_by' => User::factory()->state(['user_type' => 'super_admin']),
        ];
    }

    public function underInvestigation(): static
    {
        return $this->state(fn () => ['status' => 'under_investigation']);
    }

    public function decided(): static
    {
        return $this->state(fn () => ['status' => 'decided']);
    }
}
