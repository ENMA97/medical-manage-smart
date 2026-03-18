<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionTest extends TestCase
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

        $employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_admin_can_list_positions(): void
    {
        Position::factory()->count(3)->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->getJson('/api/positions');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_position(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/positions', [
            'code' => 'DEV-001',
            'title' => 'Software Developer',
            'title_ar' => 'مطور برمجيات',
            'department_id' => $this->department->id,
            'category' => 'technical',
            'min_salary' => 8000,
            'max_salary' => 15000,
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('positions', ['code' => 'DEV-001']);
    }

    public function test_admin_can_show_position(): void
    {
        $position = Position::factory()->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->getJson("/api/positions/{$position->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_update_position(): void
    {
        $position = Position::factory()->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->putJson("/api/positions/{$position->id}", [
            'title_ar' => 'مسمى محدث',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_delete_position_without_employees(): void
    {
        $position = Position::factory()->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/positions/{$position->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_cannot_delete_position_with_employees(): void
    {
        $position = Position::factory()->create(['department_id' => $this->department->id]);
        Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $position->id,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/positions/{$position->id}");

        $response->assertUnprocessable();
    }

    public function test_position_code_must_be_unique(): void
    {
        Position::factory()->create([
            'department_id' => $this->department->id,
            'code' => 'UNIQUE-001',
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/positions', [
            'code' => 'UNIQUE-001',
            'title' => 'Another Position',
            'title_ar' => 'مسمى آخر',
            'department_id' => $this->department->id,
        ]);

        $response->assertUnprocessable();
    }

    public function test_regular_user_cannot_manage_positions(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/positions', [
            'code' => 'TEST-001',
            'title' => 'Test',
            'title_ar' => 'اختبار',
            'department_id' => $this->department->id,
        ]);

        $response->assertForbidden();
    }
}
