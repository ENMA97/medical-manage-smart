<?php

namespace Tests\Feature;

use App\Models\CustodyItem;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustodyTest extends TestCase
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

    public function test_admin_can_list_custody_items(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/custody');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_custody_item(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/custody', [
            'employee_id' => $this->employee->id,
            'item_name' => 'Laptop',
            'item_name_ar' => 'حاسب محمول',
            'item_type' => 'electronics',
            'serial_number' => 'SN-TEST-001',
            'delivery_date' => now()->format('Y-m-d'),
            'condition_on_delivery' => 'new',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_view_custody_item(): void
    {
        $item = CustodyItem::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'item_name' => 'Mobile Phone',
            'item_name_ar' => 'هاتف جوال',
            'item_type' => 'electronics',
            'serial_number' => 'SN-VIEW-001',
            'delivery_date' => now(),
            'condition_on_delivery' => 'new',
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/custody/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_return_custody_item(): void
    {
        $item = CustodyItem::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'item_name' => 'Office Key',
            'item_name_ar' => 'مفتاح مكتب',
            'item_type' => 'key',
            'serial_number' => 'SN-RET-001',
            'delivery_date' => now()->subMonth(),
            'condition_on_delivery' => 'new',
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/custody/{$item->id}/return", [
            'condition_on_return' => 'good',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_regular_user_cannot_create_custody_item(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/custody', [
            'employee_id' => $this->employee->id,
            'item_name' => 'Laptop',
            'item_type' => 'electronics',
        ]);

        $response->assertForbidden();
    }
}
