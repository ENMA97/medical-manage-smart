<?php

namespace Tests\Feature\Api\HR;

use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\Position;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Department $department;
    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create([
            'department_id' => $this->department->id,
        ]);

        // Grant permissions
        Gate::define('hr.view', fn() => true);
        Gate::define('hr.manage', fn() => true);
    }

    /** @test */
    public function can_list_employees()
    {
        // Arrange
        Employee::factory()->count(5)->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/hr/employees');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'employee_number',
                            'name_ar',
                            'name_en',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_filter_employees_by_department()
    {
        // Arrange
        $otherDepartment = Department::factory()->create();

        Employee::factory()->count(3)->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        Employee::factory()->count(2)->create([
            'department_id' => $otherDepartment->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/hr/employees?department_id={$this->department->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }

    /** @test */
    public function can_create_employee()
    {
        // Arrange
        $employeeData = [
            'employee_number' => 'EMP001',
            'name_ar' => 'أحمد محمد',
            'name_en' => 'Ahmed Mohamed',
            'email' => 'ahmed@example.com',
            'phone' => '0501234567',
            'national_id' => '1234567890',
            'nationality' => 'SA',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'hire_date' => '2024-01-01',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hr/employees', $employeeData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP001',
            'name_ar' => 'أحمد محمد',
        ]);
    }

    /** @test */
    public function cannot_create_employee_with_duplicate_employee_number()
    {
        // Arrange
        Employee::factory()->create([
            'employee_number' => 'EMP001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $employeeData = [
            'employee_number' => 'EMP001',
            'name_ar' => 'محمد علي',
            'email' => 'mohamed@example.com',
            'hire_date' => '2024-01-01',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hr/employees', $employeeData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    }

    /** @test */
    public function can_view_single_employee()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/hr/employees/{$employee->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $employee->id,
                ],
            ]);
    }

    /** @test */
    public function can_update_employee()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'name_ar' => 'الاسم القديم',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/hr/employees/{$employee->id}", [
                'name_ar' => 'الاسم الجديد',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $employee->refresh();
        $this->assertEquals('الاسم الجديد', $employee->name_ar);
    }

    /** @test */
    public function can_delete_employee()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/hr/employees/{$employee->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Soft delete check
        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }

    /** @test */
    public function can_search_employees()
    {
        // Arrange
        Employee::factory()->create([
            'name_ar' => 'أحمد محمد علي',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        Employee::factory()->create([
            'name_ar' => 'خالد سعيد',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/hr/employees/search?q=أحمد');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function can_get_active_employees()
    {
        // Arrange
        Employee::factory()->count(3)->create([
            'is_active' => true,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        Employee::factory()->count(2)->create([
            'is_active' => false,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/hr/employees/active');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function can_get_employee_contracts()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/hr/employees/{$employee->id}/contracts");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function unauthorized_user_cannot_manage_employees()
    {
        // Arrange
        Gate::define('hr.manage', fn() => false);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hr/employees', [
                'name_ar' => 'Test',
            ]);

        // Assert
        $response->assertStatus(403);
    }
}
