<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'code' => 'POS-' . strtoupper(fake()->unique()->bothify('???##')),
            'title' => fake()->jobTitle(),
            'title_ar' => fake()->jobTitle(),
            'department_id' => Department::factory(),
            'category' => fake()->randomElement(['medical', 'administrative', 'technical', 'support']),
            'description' => fake()->sentence(),
            'requirements' => fake()->sentence(),
            'min_salary' => fake()->numberBetween(5000, 10000),
            'max_salary' => fake()->numberBetween(10000, 30000),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
