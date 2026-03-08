<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\GeneratedLetter;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LetterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Department $department;
    private Employee $employee;
    private LetterTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $this->employee->id]);

        $this->template = LetterTemplate::create([
            'id' => Str::uuid(),
            'name' => 'Salary Certificate',
            'name_ar' => 'شهادة راتب',
            'letter_type' => 'salary_certificate',
            'body_template' => 'This certifies that {employee_name} works at our company.',
            'body_template_ar' => 'نشهد بأن {employee_name} يعمل لدينا.',
            'requires_approval' => true,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_list_templates(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/letter-templates');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_list_letters(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/letters');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_letter(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/letters', [
            'template_id' => $this->template->id,
            'employee_id' => $this->employee->id,
            'notes' => 'للبنك',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_approve_letter(): void
    {
        $letter = GeneratedLetter::create([
            'id' => Str::uuid(),
            'template_id' => $this->template->id,
            'employee_id' => $this->employee->id,
            'letter_number' => 'LTR-TEST-00001',
            'letter_type' => 'salary_certificate',
            'content' => 'Test content',
            'content_ar' => 'محتوى تجريبي',
            'status' => 'pending',
            'generated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/letters/{$letter->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('generated_letters', [
            'id' => $letter->id,
            'status' => 'approved',
        ]);
    }

    public function test_cannot_approve_non_pending_letter(): void
    {
        $letter = GeneratedLetter::create([
            'id' => Str::uuid(),
            'template_id' => $this->template->id,
            'employee_id' => $this->employee->id,
            'letter_number' => 'LTR-TEST-00002',
            'letter_type' => 'salary_certificate',
            'content' => 'Test content',
            'content_ar' => 'محتوى تجريبي',
            'status' => 'approved',
            'generated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/letters/{$letter->id}/approve");

        $response->assertUnprocessable();
    }

    public function test_regular_user_cannot_create_letter(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/letters', [
            'template_id' => $this->template->id,
            'employee_id' => $this->employee->id,
        ]);

        $response->assertForbidden();
    }
}
