<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $regularEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $regularEmployee->id]);
    }

    public function test_admin_can_list_employees(): void
    {
        Employee::factory()->count(3)->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->getJson('/api/employees');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_regular_user_cannot_list_employees(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/employees');

        $response->assertForbidden();
    }

    public function test_admin_can_create_employee(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/employees', [
            'employee_number' => 'NEW-001',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '0551234567',
            'department_id' => $this->department->id,
            'hire_date' => '2024-01-01',
            'gender' => 'male',
            'national_id' => '1234567890',
            'nationality' => 'SA',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_view_employee(): void
    {
        $employee = Employee::factory()->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_update_employee(): void
    {
        $employee = Employee::factory()->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->putJson("/api/employees/{$employee->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertOk();
    }

    public function test_admin_can_delete_employee(): void
    {
        $employee = Employee::factory()->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/employees/{$employee->id}");

        $response->assertOk();
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_unauthenticated_cannot_access_employees(): void
    {
        $response = $this->getJson('/api/employees');
        $response->assertUnauthorized();
    }
}
