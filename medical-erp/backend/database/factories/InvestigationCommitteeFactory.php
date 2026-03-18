<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\InvestigationCommittee;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvestigationCommitteeFactory extends Factory
{
    protected $model = InvestigationCommittee::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'committee_number' => 'COM-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => 'Investigation Committee',
            'name_ar' => 'لجنة تحقيق',
            'violation_id' => Violation::factory(),
            'chairman_id' => Employee::factory(),
            'formation_date' => now()->toDateString(),
            'deadline' => now()->addDays(14)->toDateString(),
            'status' => 'formed',
            'mandate_ar' => 'التحقيق في المخالفة وتقديم التوصيات',
            'formed_by' => User::factory()->state(['user_type' => 'super_admin']),
        ];
    }
}
