<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
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

    public function test_admin_can_list_shifts(): void
    {
        Shift::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/shifts');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_shift(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/shifts', [
            'code' => 'SH-TEST',
            'name' => 'Test Shift',
            'name_ar' => 'وردية اختبار',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'break_minutes' => 30,
            'grace_period_minutes' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('shifts', ['code' => 'SH-TEST']);
    }

    public function test_shift_code_must_be_unique(): void
    {
        Shift::factory()->create(['code' => 'SH-UNIQUE']);

        $response = $this->actingAs($this->admin)->postJson('/api/shifts', [
            'code' => 'SH-UNIQUE',
            'name' => 'Another Shift',
            'name_ar' => 'وردية أخرى',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_update_shift(): void
    {
        $shift = Shift::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("/api/shifts/{$shift->id}", [
            'name_ar' => 'وردية محدثة',
            'grace_period_minutes' => 20,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name_ar', 'وردية محدثة');
    }

    public function test_admin_can_delete_unused_shift(): void
    {
        $shift = Shift::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/shifts/{$shift->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_cannot_delete_shift_with_assignments(): void
    {
        $shift = Shift::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $this->department->id]);
        ShiftAssignment::factory()->create([
            'shift_id' => $shift->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/shifts/{$shift->id}");

        $response->assertUnprocessable();
    }

    public function test_admin_can_assign_employees_to_shift(): void
    {
        $shift = Shift::factory()->create();
        $employees = Employee::factory()->count(2)->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->postJson("/api/shifts/{$shift->id}/assign", [
            'employee_ids' => $employees->pluck('id')->all(),
            'effective_from' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('shift_assignments', [
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
            ]);
        }
    }

    public function test_assigning_new_shift_closes_previous_open_assignment(): void
    {
        $employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $oldShift = Shift::factory()->create();
        $oldAssignment = ShiftAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $oldShift->id,
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
        ]);

        $newShift = Shift::factory()->create();
        $response = $this->actingAs($this->admin)->postJson("/api/shifts/{$newShift->id}/assign", [
            'employee_ids' => [$employee->id],
            'effective_from' => now()->toDateString(),
        ]);

        $response->assertCreated();

        $oldAssignment->refresh();
        $this->assertNotNull($oldAssignment->effective_to);
        $this->assertSame(
            now()->subDay()->toDateString(),
            $oldAssignment->effective_to->toDateString()
        );
    }

    public function test_admin_can_list_shift_assignments(): void
    {
        ShiftAssignment::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/shift-assignments');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_unassign_shift(): void
    {
        $assignment = ShiftAssignment::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/shift-assignments/{$assignment->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('shift_assignments', ['id' => $assignment->id]);
    }

    public function test_regular_user_cannot_manage_shifts(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/shifts', [
            'code' => 'SH-X',
            'name' => 'X',
            'name_ar' => 'س',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $response->assertForbidden();
    }
}
