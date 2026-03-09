<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_import_requires_authentication(): void
    {
        $response = $this->postJson('/api/import/employees');

        $response->assertUnauthorized();
    }

    public function test_import_requires_admin_role(): void
    {
        $file = UploadedFile::fake()->create('employees.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/import/employees', ['file' => $file]);

        $response->assertForbidden();
    }

    public function test_import_requires_file(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/import/employees', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_import_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('employees.txt', 100, 'text/plain');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/import/employees', ['file' => $file]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_import_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('employees.xlsx', 11000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/import/employees', ['file' => $file]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_import_validates_type_parameter(): void
    {
        $file = UploadedFile::fake()->create('employees.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/import/employees', [
                'file' => $file,
                'type' => 'invalid_type',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_template_download_requires_authentication(): void
    {
        $response = $this->getJson('/api/import/template');

        $response->assertUnauthorized();
    }

    public function test_template_download_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/import/template');

        $response->assertForbidden();
    }
}
