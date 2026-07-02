<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'type' => 'string',
            'group' => fake()->randomElement(['general', 'hr', 'payroll', 'leave', 'system']),
            'label' => fake()->words(3, true),
            'label_ar' => 'إعداد',
            'description' => fake()->sentence(),
            'is_public' => true,
            'is_editable' => true,
        ];
    }
}
