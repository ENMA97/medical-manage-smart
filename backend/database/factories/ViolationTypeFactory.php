<?php

namespace Database\Factories;

use App\Models\ViolationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ViolationTypeFactory extends Factory
{
    protected $model = ViolationType::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'code' => 'VT-' . strtoupper(fake()->unique()->bothify('???##')),
            'name' => fake()->sentence(3),
            'name_ar' => 'مخالفة تجريبية',
            'category' => fake()->randomElement(['attendance', 'conduct', 'safety', 'confidentiality']),
            'category_ar' => 'مخالفات عامة',
            'severity' => fake()->randomElement(['minor', 'moderate', 'major', 'critical']),
            'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
            'requires_investigation' => false,
            'penalties' => [
                ['occurrence' => 1, 'penalty' => 'verbal_warning', 'penalty_ar' => 'إنذار شفهي', 'details_ar' => 'تنبيه شفهي'],
                ['occurrence' => 2, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                ['occurrence' => 3, 'penalty' => 'deduction_1_day', 'penalty_ar' => 'خصم يوم', 'deduction_days' => 1, 'details_ar' => 'خصم أجر يوم'],
            ],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function requiresInvestigation(): static
    {
        return $this->state(fn () => ['requires_investigation' => true, 'severity' => 'critical']);
    }
}
