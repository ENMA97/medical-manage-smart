<?php

namespace Database\Factories\Leave;

use App\Models\Leave\LeaveRequest;
use App\Models\Leave\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+1 month');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(1, 10) . ' days');

        return [
            'request_number' => 'LR-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'employee_id' => $this->faker->uuid(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'working_days' => $this->faker->numberBetween(1, 10),
            'reason' => $this->faker->sentence(),
            'status' => 'draft',
            'is_medical_staff' => $this->faker->boolean(30),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function pendingSupervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_supervisor',
            'submitted_at' => now(),
        ]);
    }

    public function pendingAdminManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_admin_manager',
            'submitted_at' => now(),
        ]);
    }

    public function pendingHr(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_hr',
            'submitted_at' => now(),
        ]);
    }

    public function pendingDelegate(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_delegate',
            'submitted_at' => now(),
            'delegate_id' => $this->faker->uuid(),
        ]);
    }

    public function formCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'form_completed',
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'submitted_at' => now(),
        ]);
    }

    public function forMedicalStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_medical_staff' => true,
        ]);
    }
}
