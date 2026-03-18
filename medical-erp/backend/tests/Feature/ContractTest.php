<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContractTest extends TestCase
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

    public function test_admin_can_list_contracts(): void
    {
        Contract::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'contract_number' => 'CNT-TEST-00001',
            'contract_type' => 'full_time',
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2025-01-01',
            'basic_salary' => 5000,
            'housing_allowance' => 1250,
            'transport_allowance' => 500,
            'food_allowance' => 0,
            'phone_allowance' => 0,
            'other_allowances' => 0,
            'total_salary' => 6750,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/contracts');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_contract(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/contracts', [
            'employee_id' => $this->employee->id,
            'contract_type' => 'full_time',
            'start_date' => '2024-01-01',
            'end_date' => '2025-01-01',
            'basic_salary' => 5000,
            'housing_allowance' => 1250,
            'transport_allowance' => 500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_view_contract(): void
    {
        $contract = Contract::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'contract_number' => 'CNT-TEST-00002',
            'contract_type' => 'full_time',
            'status' => 'active',
            'start_date' => '2024-01-01',
            'basic_salary' => 5000,
            'housing_allowance' => 0,
            'transport_allowance' => 0,
            'food_allowance' => 0,
            'phone_allowance' => 0,
            'other_allowances' => 0,
            'total_salary' => 5000,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/contracts/{$contract->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_renew_contract(): void
    {
        $contract = Contract::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'contract_number' => 'CNT-TEST-00003',
            'contract_type' => 'full_time',
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2025-01-01',
            'basic_salary' => 5000,
            'housing_allowance' => 1250,
            'transport_allowance' => 500,
            'food_allowance' => 0,
            'phone_allowance' => 0,
            'other_allowances' => 0,
            'total_salary' => 6750,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/contracts/{$contract->id}/renew", [
            'start_date' => '2025-01-01',
            'end_date' => '2026-01-01',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'renewed',
        ]);
    }

    public function test_regular_user_cannot_create_contract(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/contracts', [
            'employee_id' => $this->employee->id,
            'contract_type' => 'full_time',
            'start_date' => '2024-01-01',
            'basic_salary' => 5000,
        ]);

        $response->assertForbidden();
    }
}
