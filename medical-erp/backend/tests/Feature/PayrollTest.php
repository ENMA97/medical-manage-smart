<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollTest extends TestCase
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

        // Create an active contract for the employee
        Contract::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'contract_number' => 'CNT-PAY-TEST-001',
            'contract_type' => 'full_time',
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2025-12-31',
            'basic_salary' => 5000,
            'housing_allowance' => 1250,
            'transport_allowance' => 500,
            'food_allowance' => 0,
            'phone_allowance' => 0,
            'other_allowances' => 0,
            'total_salary' => 6750,
        ]);
    }

    public function test_admin_can_list_payrolls(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/payrolls');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_payroll(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/payrolls', [
            'month' => 3,
            'year' => 2026,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_view_payroll_detail(): void
    {
        $payroll = Payroll::create([
            'id' => Str::uuid(),
            'payroll_number' => 'PAY-TEST-001',
            'month' => 3,
            'year' => 2026,
            'status' => 'draft',
            'total_basic_salary' => 5000,
            'total_allowances' => 1750,
            'total_gross_salary' => 6750,
            'total_deductions' => 487.50,
            'total_net_salary' => 6262.50,
            'total_gosi_employee' => 487.50,
            'total_gosi_employer' => 587.50,
            'employees_count' => 1,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/payrolls/{$payroll->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_approve_payroll(): void
    {
        $payroll = Payroll::create([
            'id' => Str::uuid(),
            'payroll_number' => 'PAY-TEST-002',
            'month' => 3,
            'year' => 2026,
            'status' => 'draft',
            'total_basic_salary' => 5000,
            'total_allowances' => 1750,
            'total_gross_salary' => 6750,
            'total_deductions' => 487.50,
            'total_net_salary' => 6262.50,
            'total_gosi_employee' => 487.50,
            'total_gosi_employer' => 587.50,
            'employees_count' => 1,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/payrolls/{$payroll->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'approved',
        ]);
    }

    public function test_cannot_approve_already_approved_payroll(): void
    {
        $payroll = Payroll::create([
            'id' => Str::uuid(),
            'payroll_number' => 'PAY-TEST-003',
            'month' => 3,
            'year' => 2026,
            'status' => 'approved',
            'total_basic_salary' => 5000,
            'total_allowances' => 1750,
            'total_gross_salary' => 6750,
            'total_deductions' => 487.50,
            'total_net_salary' => 6262.50,
            'total_gosi_employee' => 487.50,
            'total_gosi_employer' => 587.50,
            'employees_count' => 1,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/payrolls/{$payroll->id}/approve");

        $response->assertUnprocessable();
    }

    public function test_regular_user_cannot_create_payroll(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/payrolls', [
            'month' => 3,
            'year' => 2026,
        ]);

        $response->assertForbidden();
    }
}
