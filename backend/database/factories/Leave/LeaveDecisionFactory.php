<?php

namespace Database\Factories\Leave;

use App\Models\Leave\LeaveDecision;
use App\Models\Leave\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveDecisionFactory extends Factory
{
    protected $model = LeaveDecision::class;

    public function definition(): array
    {
        return [
            'decision_number' => 'LD-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'leave_request_id' => LeaveRequest::factory()->formCompleted(),
            'status' => 'pending_admin_manager',
            'requires_gm_approval' => false,
            'forwarded_to_gm' => false,
        ];
    }

    public function pendingAdminManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_admin_manager',
        ]);
    }

    public function pendingMedicalDirector(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_medical_director',
        ]);
    }

    public function pendingGeneralManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_general_manager',
            'forwarded_to_gm' => true,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'admin_manager_action' => 'approve',
            'approved_at' => now(),
            'approved_by' => $this->faker->uuid(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'admin_manager_action' => 'reject',
        ]);
    }

    public function forwardedToGm(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_general_manager',
            'admin_manager_action' => 'forward_to_gm',
            'forwarded_to_gm' => true,
        ]);
    }

    public function approvedByGm(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'admin_manager_action' => 'forward_to_gm',
            'forwarded_to_gm' => true,
            'gm_action' => 'approve',
            'gm_approved_at' => now(),
            'gm_approved_by' => $this->faker->uuid(),
        ]);
    }
}
