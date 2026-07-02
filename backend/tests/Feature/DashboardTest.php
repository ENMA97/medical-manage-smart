<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Department $department;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $this->employee->id]);
    }

    public function test_authenticated_user_can_get_summary(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/dashboard/summary');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_employees',
                    'active_employees',
                    'on_leave',
                    'departments_count',
                    'pending_leave_requests',
                    'pending_resignations',
                ],
            ]);
    }

    public function test_authenticated_user_can_get_employee_stats(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/dashboard/employee-stats');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'by_department',
                    'by_employment_type',
                    'by_status',
                    'by_gender',
                ],
            ]);
    }

    public function test_authenticated_user_can_get_leave_stats(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/dashboard/leave-stats');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_requests',
                    'pending',
                    'approved',
                    'rejected',
                    'cancelled',
                    'currently_on_leave',
                ],
            ]);
    }

    public function test_authenticated_user_can_get_alerts(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/dashboard/alerts');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'expiring_contracts',
                    'pending_leave_requests',
                    'pending_resignations',
                ],
            ]);
    }

    public function test_summary_counts_employees_correctly(): void
    {
        Employee::factory()->count(3)->create([
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/dashboard/summary');

        $response->assertOk();
        // 2 from setUp + 3 new = 5 total, all active
        $this->assertGreaterThanOrEqual(5, $response->json('data.total_employees'));
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/dashboard/summary');

        $response->assertUnauthorized();
    }
}
