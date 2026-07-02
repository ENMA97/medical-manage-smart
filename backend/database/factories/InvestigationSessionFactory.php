<?php

namespace Database\Factories;

use App\Models\InvestigationCommittee;
use App\Models\InvestigationSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvestigationSessionFactory extends Factory
{
    protected $model = InvestigationSession::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'committee_id' => InvestigationCommittee::factory(),
            'session_number' => 1,
            'session_date' => now()->addDays(3),
            'location' => 'قاعة الاجتماعات',
            'agenda_ar' => 'مناقشة المخالفة وسماع إفادة الموظف',
            'employee_attended' => true,
            'status' => 'scheduled',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'minutes_ar' => 'تم سماع إفادة الموظف وتقديم التوصيات',
            'employee_response_ar' => 'أقر الموظف بالمخالفة وتعهد بعدم التكرار',
        ]);
    }
}
