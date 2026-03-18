<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createEmployeeWithUser(array $overrides = []): array
    {
        $department = Department::factory()->create();
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'phone' => '0512345678',
            'employee_number' => 'EMP-0001',
        ]);
        $user = User::factory()->create(array_merge([
            'employee_id' => $employee->id,
            'phone' => $employee->phone,
            'username' => $employee->employee_number,
        ], $overrides));

        return [$employee, $user, $department];
    }

    public function test_login_with_valid_credentials(): void
    {
        [$employee] = $this->createEmployeeWithUser();

        $response = $this->postJson('/api/auth/login', [
            'employee_number' => 'EMP-0001',
            'phone' => '0512345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_with_wrong_employee_number(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'employee_number' => 'WRONG',
            'phone' => '0512345678',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_wrong_phone(): void
    {
        $this->createEmployeeWithUser();

        $response = $this->postJson('/api/auth/login', [
            'employee_number' => 'EMP-0001',
            'phone' => '0599999999',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_inactive_account(): void
    {
        $this->createEmployeeWithUser(['is_active' => false]);

        $response = $this->postJson('/api/auth/login', [
            'employee_number' => 'EMP-0001',
            'phone' => '0512345678',
        ]);

        $response->assertStatus(422);
    }

    public function test_me_returns_authenticated_user(): void
    {
        [, $user] = $this->createEmployeeWithUser();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    public function test_logout(): void
    {
        [, $user] = $this->createEmployeeWithUser();

        $response = $this->actingAs($user)->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_update_language(): void
    {
        [, $user] = $this->createEmployeeWithUser();

        $response = $this->actingAs($user)->putJson('/api/auth/language', [
            'language' => 'en',
        ]);

        $response->assertOk();
        $this->assertEquals('en', $user->fresh()->preferred_language);
    }
}
