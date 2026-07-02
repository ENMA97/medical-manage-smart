<?php

namespace Database\Factories;

use App\Models\CommitteeMember;
use App\Models\Employee;
use App\Models\InvestigationCommittee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CommitteeMemberFactory extends Factory
{
    protected $model = CommitteeMember::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'committee_id' => InvestigationCommittee::factory(),
            'employee_id' => Employee::factory(),
            'role' => fake()->randomElement(['chairman', 'member', 'secretary']),
            'role_ar' => fake()->randomElement(['رئيس', 'عضو', 'مقرر']),
        ];
    }
}
