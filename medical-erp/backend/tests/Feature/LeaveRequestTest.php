<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Department $department;
    private Employee $employee;
    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $this->employee->id]);

        $this->leaveType = LeaveType::create([
            'id' => Str::uuid(),
            'code' => 'ANNUAL',
            'name' => 'Annual Leave',
            'name_ar' => 'إجازة سنوية',
            'category' => 'annual',
            'default_days_per_year' => 21,
            'max_days_per_request' => 21,
            'min_days_per_request' => 1,
            'is_paid' => true,
            'pay_percentage' => 100,
            'is_active' => true,
        ]);
    }

    public function test_employee_can_list_leave_requests(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/leave-requests');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_employee_can_create_leave_request(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/leave-requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-05',
            'total_days' => 5,
            'reason' => 'إجازة عائلية',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_approve_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'id' => Str::uuid(),
            'request_number' => 'LR-TEST-001',
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-05',
            'total_days' => 5,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/leave-requests/{$leave->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_reject_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'id' => Str::uuid(),
            'request_number' => 'LR-TEST-002',
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-05',
            'total_days' => 5,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/leave-requests/{$leave->id}/reject", [
            'comment' => 'ضغط عمل',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_employee_can_cancel_own_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'id' => Str::uuid(),
            'request_number' => 'LR-TEST-003',
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-05',
            'total_days' => 5,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->regularUser)->postJson("/api/leave-requests/{$leave->id}/cancel", [
            'cancellation_reason' => 'تغيرت الخطط',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_unauthenticated_cannot_access_leave_requests(): void
    {
        $response = $this->getJson('/api/leave-requests');
        $response->assertUnauthorized();
    }
}
