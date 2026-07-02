<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'code' => strtoupper($this->faker->unique()->lexify('DEPT-???')),
            'name' => $this->faker->words(2, true),
            'name_ar' => 'قسم ' . $this->faker->word(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
