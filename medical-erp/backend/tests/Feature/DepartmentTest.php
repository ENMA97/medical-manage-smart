<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::factory()->create();
        $emp = Employee::factory()->create(['department_id' => $dept->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $emp->id]);
    }

    public function test_admin_can_list_departments(): void
    {
        Department::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/departments');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_department(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/departments', [
            'code' => 'IT',
            'name' => 'Information Technology',
            'name_ar' => 'تقنية المعلومات',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_update_department(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("/api/departments/{$department->id}", [
            'name' => 'Updated Name',
            'name_ar' => 'اسم محدث',
        ]);

        $response->assertOk();
    }

    public function test_admin_can_delete_department(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/departments/{$department->id}");

        $response->assertOk();
    }
}
