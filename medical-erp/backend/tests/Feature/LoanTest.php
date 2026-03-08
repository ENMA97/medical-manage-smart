<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoanTest extends TestCase
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

    public function test_admin_can_list_loans(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/loans');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_loan(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/loans', [
            'employee_id' => $this->employee->id,
            'loan_amount' => 5000,
            'monthly_deduction' => 500,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'reason' => 'حالة طارئة',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('employee_loans', [
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_loan(): void
    {
        $loan = EmployeeLoan::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'loan_number' => 'LOAN-TEST-0001',
            'loan_amount' => 5000,
            'monthly_deduction' => 500,
            'remaining_amount' => 5000,
            'total_installments' => 10,
            'paid_installments' => 0,
            'remaining_installments' => 10,
            'start_date' => now()->addDay(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/loans/{$loan->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('employee_loans', [
            'id' => $loan->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_reject_loan(): void
    {
        $loan = EmployeeLoan::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'loan_number' => 'LOAN-TEST-0002',
            'loan_amount' => 5000,
            'monthly_deduction' => 500,
            'remaining_amount' => 5000,
            'total_installments' => 10,
            'paid_installments' => 0,
            'remaining_installments' => 10,
            'start_date' => now()->addDay(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/loans/{$loan->id}/reject");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('employee_loans', [
            'id' => $loan->id,
            'status' => 'rejected',
        ]);
    }

    public function test_cannot_approve_non_pending_loan(): void
    {
        $loan = EmployeeLoan::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'loan_number' => 'LOAN-TEST-0003',
            'loan_amount' => 5000,
            'monthly_deduction' => 500,
            'remaining_amount' => 5000,
            'total_installments' => 10,
            'paid_installments' => 0,
            'remaining_installments' => 10,
            'start_date' => now()->addDay(),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/loans/{$loan->id}/approve");

        $response->assertUnprocessable();
    }

    public function test_regular_user_cannot_create_loan(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/loans', [
            'employee_id' => $this->employee->id,
            'loan_amount' => 5000,
            'monthly_deduction' => 500,
            'start_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertForbidden();
    }
}
