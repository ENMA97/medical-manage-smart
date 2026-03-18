<?php

namespace Database\Factories;

use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeaveApprovalFactory extends Factory
{
    protected $model = LeaveApproval::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'leave_request_id' => LeaveRequest::factory(),
            'step_order' => 1,
            'approval_role' => fake()->randomElement(['direct_manager', 'hr_manager', 'general_manager']),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approver_id' => User::factory()->state(['user_type' => 'hr_manager']),
            'actioned_at' => now(),
        ]);
    }
}
