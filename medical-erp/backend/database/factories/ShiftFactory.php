<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('SH-####'),
            'name' => 'Morning Shift',
            'name_ar' => 'الوردية الصباحية',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'break_minutes' => 30,
            'grace_period_minutes' => 15,
            'is_overnight' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function overnight(): static
    {
        return $this->state(fn () => [
            'name' => 'Night Shift',
            'name_ar' => 'الوردية الليلية',
            'start_time' => '20:00',
            'end_time' => '04:00',
            'is_overnight' => true,
        ]);
    }
}
