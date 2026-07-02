<?php

namespace Database\Factories;

use App\Models\DisciplinaryDecision;
use App\Models\Employee;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DisciplinaryDecisionFactory extends Factory
{
    protected $model = DisciplinaryDecision::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'decision_number' => 'DEC-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'violation_id' => Violation::factory(),
            'employee_id' => Employee::factory(),
            'penalty_type' => 'written_warning',
            'penalty_type_ar' => 'إنذار كتابي',
            'penalty_details_ar' => 'إنذار كتابي أول',
            'effective_date' => now()->toDateString(),
            'justification' => 'بناءً على المخالفة المرتكبة',
            'justification_ar' => 'بناءً على المخالفة المرتكبة',
            'labor_law_reference' => 'المادة 66',
            'status' => 'issued',
            'decided_by' => User::factory()->state(['user_type' => 'super_admin']),
            'decided_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'final',
            'approved_by' => User::factory()->state(['user_type' => 'super_admin']),
            'approved_at' => now(),
        ]);
    }
}
