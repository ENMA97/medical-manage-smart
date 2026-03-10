<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Resignation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResignationTest extends TestCase
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

    public function test_employee_can_list_resignations(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/resignations');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_employee_can_submit_resignation(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/resignations', [
            'employee_id' => $this->employee->id,
            'type' => 'resignation',
            'request_date' => now()->addDays(30)->format('Y-m-d'),
            'last_working_day' => now()->addDays(60)->format('Y-m-d'),
            'reason' => 'فرصة عمل أفضل',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_approve_resignation(): void
    {
        $resignation = Resignation::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'type' => 'resignation',
            'request_date' => now()->addDays(30),
            'last_working_day' => now()->addDays(60),
            'reason' => 'فرصة عمل أفضل',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/resignations/{$resignation->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_reject_resignation(): void
    {
        $resignation = Resignation::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'type' => 'resignation',
            'request_date' => now()->addDays(30),
            'last_working_day' => now()->addDays(60),
            'reason' => 'أسباب شخصية',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/resignations/{$resignation->id}/reject", [
            'remarks' => 'نحتاجك في الفريق',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_unauthenticated_cannot_submit_resignation(): void
    {
        $response = $this->postJson('/api/resignations', [
            'request_date' => now()->addDays(30)->format('Y-m-d'),
            'reason' => 'test',
        ]);

        $response->assertUnauthorized();
    }
}
