<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $type): User
    {
        $dept = Department::factory()->create();
        $emp = Employee::factory()->create(['department_id' => $dept->id]);
        return User::factory()->create([
            'employee_id' => $emp->id,
            'user_type' => $type,
        ]);
    }

    public function test_super_admin_can_access_hr_routes(): void
    {
        $user = $this->makeUser('super_admin');

        $response = $this->actingAs($user)->getJson('/api/employees');
        $response->assertOk();
    }

    public function test_hr_manager_can_access_hr_routes(): void
    {
        $user = $this->makeUser('hr_manager');

        $response = $this->actingAs($user)->getJson('/api/employees');
        $response->assertOk();
    }

    public function test_employee_cannot_access_hr_routes(): void
    {
        $user = $this->makeUser('employee');

        $response = $this->actingAs($user)->getJson('/api/employees');
        $response->assertForbidden();
    }

    public function test_department_manager_cannot_access_hr_routes(): void
    {
        $user = $this->makeUser('department_manager');

        $response = $this->actingAs($user)->getJson('/api/employees');
        $response->assertForbidden();
    }

    public function test_any_user_can_access_dashboard(): void
    {
        $user = $this->makeUser('employee');

        $response = $this->actingAs($user)->getJson('/api/dashboard/summary');
        $response->assertOk();
    }

    public function test_any_user_can_access_leave_requests(): void
    {
        $user = $this->makeUser('employee');

        $response = $this->actingAs($user)->getJson('/api/leave-requests');
        $response->assertOk();
    }

    public function test_employee_cannot_approve_leave(): void
    {
        $user = $this->makeUser('employee');

        $response = $this->actingAs($user)->postJson('/api/leave-requests/fake-id/approve');
        $response->assertForbidden();
    }

    public function test_employee_cannot_access_settings(): void
    {
        $user = $this->makeUser('employee');

        $response = $this->actingAs($user)->getJson('/api/settings');
        $response->assertForbidden();
    }

    public function test_inactive_user_is_blocked(): void
    {
        $user = $this->makeUser('super_admin');
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->getJson('/api/auth/me');
        $response->assertForbidden();
    }
}
