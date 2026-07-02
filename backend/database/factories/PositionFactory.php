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
            'code' => $this->faker->unique()->numerify('POS-####'),
            'title' => $this->faker->jobTitle(),
            'title_ar' => 'وظيفة',
            'department_id' => Department::factory(),
            'category' => $this->faker->randomElement(['medical', 'administrative', 'technical']),
            'min_salary' => 5000,
            'max_salary' => 15000,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
