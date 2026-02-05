<?php

namespace Database\Factories\Leave;

use App\Models\Leave\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        $categories = ['annual', 'sick', 'emergency', 'unpaid', 'maternity', 'paternity', 'hajj', 'marriage', 'bereavement', 'study', 'compensatory', 'other'];

        return [
            'code' => strtoupper($this->faker->unique()->lexify('LT???')),
            'name_ar' => $this->faker->randomElement(['إجازة سنوية', 'إجازة مرضية', 'إجازة طارئة']),
            'name_en' => $this->faker->randomElement(['Annual Leave', 'Sick Leave', 'Emergency Leave']),
            'category' => $this->faker->randomElement($categories),
            'description_ar' => $this->faker->sentence(),
            'description_en' => $this->faker->sentence(),
            'default_days' => $this->faker->numberBetween(5, 30),
            'max_days_per_request' => $this->faker->numberBetween(5, 21),
            'min_days_per_request' => 1,
            'requires_attachment' => $this->faker->boolean(30),
            'requires_medical_certificate' => $this->faker->boolean(20),
            'is_paid' => $this->faker->boolean(80),
            'affects_annual_leave' => $this->faker->boolean(30),
            'can_be_carried_over' => $this->faker->boolean(50),
            'max_carry_over_days' => $this->faker->numberBetween(5, 15),
            'advance_notice_days' => $this->faker->numberBetween(0, 14),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 20),
            'color_code' => $this->faker->hexColor(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'ANNUAL',
            'name_ar' => 'إجازة سنوية',
            'name_en' => 'Annual Leave',
            'category' => 'annual',
            'default_days' => 21,
            'is_paid' => true,
            'can_be_carried_over' => true,
        ]);
    }

    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'SICK',
            'name_ar' => 'إجازة مرضية',
            'name_en' => 'Sick Leave',
            'category' => 'sick',
            'default_days' => 120,
            'requires_medical_certificate' => true,
            'advance_notice_days' => 0,
        ]);
    }
}
