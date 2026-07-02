<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftAssignmentFactory extends Factory
{
    protected $model = ShiftAssignment::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'shift_id' => Shift::factory(),
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
        ];
    }
}
