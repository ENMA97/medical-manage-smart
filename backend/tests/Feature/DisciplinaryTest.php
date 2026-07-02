<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Models\DisciplinaryDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DisciplinaryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Department $department;
    private Employee $employee;
    private Employee $adminEmployee;
    private ViolationType $violationType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $this->adminEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $this->adminEmployee->id]);

        $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $this->employee->id]);

        $this->violationType = ViolationType::create([
            'id' => Str::uuid(),
            'code' => 'TEST-001',
            'name' => 'Test Violation',
            'name_ar' => 'مخالفة تجريبية',
            'category' => 'conduct',
            'category_ar' => 'سلوك',
            'severity' => 'moderate',
            'labor_law_article' => 'المادة 66',
            'requires_investigation' => false,
            'penalties' => [
                ['occurrence' => 1, 'penalty' => 'verbal_warning', 'penalty_ar' => 'إنذار شفهي', 'details_ar' => 'تنبيه شفهي'],
                ['occurrence' => 2, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                ['occurrence' => 3, 'penalty' => 'deduction_1_day', 'penalty_ar' => 'خصم يوم', 'deduction_days' => 1, 'details_ar' => 'خصم أجر يوم'],
            ],
            'is_active' => true,
        ]);
    }

    public function test_admin_can_list_violation_types(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/violation-types');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_get_penalty_suggestion(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            "/api/violation-types/{$this->violationType->id}/suggest-penalty?employee_id={$this->employee->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.occurrence_number', 1)
            ->assertJsonPath('data.suggested_penalty.penalty', 'verbal_warning');
    }

    public function test_admin_can_list_violations(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/violations');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_violation(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/violations', [
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
            'violation_date' => now()->format('Y-m-d'),
            'description' => 'تأخر عن الدوام بدون عذر مقبول',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('violations', [
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
        ]);
    }

    public function test_admin_can_view_violation(): void
    {
        $violation = Violation::create([
            'id' => Str::uuid(),
            'violation_number' => 'VIO-TEST-00001',
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
            'violation_date' => now(),
            'description' => 'مخالفة تجريبية',
            'occurrence_number' => 1,
            'status' => 'reported',
            'reported_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/violations/{$violation->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_form_committee(): void
    {
        $violationType = ViolationType::create([
            'id' => Str::uuid(),
            'code' => 'TEST-INV',
            'name' => 'Investigation Required',
            'name_ar' => 'تتطلب تحقيق',
            'category' => 'safety',
            'category_ar' => 'سلامة',
            'severity' => 'critical',
            'requires_investigation' => true,
            'is_active' => true,
        ]);

        $violation = Violation::create([
            'id' => Str::uuid(),
            'violation_number' => 'VIO-TEST-00002',
            'employee_id' => $this->employee->id,
            'violation_type_id' => $violationType->id,
            'violation_date' => now(),
            'description' => 'مخالفة تتطلب تحقيق',
            'occurrence_number' => 1,
            'status' => 'reported',
            'reported_by' => $this->admin->id,
        ]);

        $member2 = Employee::factory()->create(['department_id' => $this->department->id]);

        $response = $this->actingAs($this->admin)->postJson("/api/violations/{$violation->id}/committee", [
            'name' => 'Investigation Committee',
            'name_ar' => 'لجنة تحقيق',
            'chairman_id' => $this->adminEmployee->id,
            'members' => [
                ['employee_id' => $this->adminEmployee->id, 'role' => 'chairman', 'role_ar' => 'رئيس'],
                ['employee_id' => $member2->id, 'role' => 'member', 'role_ar' => 'عضو'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('violations', [
            'id' => $violation->id,
            'status' => 'under_investigation',
        ]);
    }

    public function test_admin_can_issue_decision(): void
    {
        $violation = Violation::create([
            'id' => Str::uuid(),
            'violation_number' => 'VIO-TEST-00003',
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
            'violation_date' => now(),
            'description' => 'مخالفة بانتظار قرار',
            'occurrence_number' => 1,
            'status' => 'reported',
            'reported_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/violations/{$violation->id}/decision", [
            'penalty_type' => 'verbal_warning',
            'penalty_type_ar' => 'إنذار شفهي',
            'effective_date' => now()->format('Y-m-d'),
            'justification' => 'بناءً على المخالفة المرتكبة',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('violations', [
            'id' => $violation->id,
            'status' => 'decided',
        ]);
    }

    public function test_admin_can_approve_decision(): void
    {
        $violation = Violation::create([
            'id' => Str::uuid(),
            'violation_number' => 'VIO-TEST-00004',
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
            'violation_date' => now(),
            'description' => 'مخالفة تم إصدار قرار لها',
            'occurrence_number' => 1,
            'status' => 'decided',
            'reported_by' => $this->admin->id,
        ]);

        $decision = DisciplinaryDecision::create([
            'id' => Str::uuid(),
            'decision_number' => 'DEC-TEST-00001',
            'violation_id' => $violation->id,
            'employee_id' => $this->employee->id,
            'penalty_type' => 'written_warning',
            'penalty_type_ar' => 'إنذار كتابي',
            'effective_date' => now(),
            'justification' => 'بناءً على التحقيق',
            'status' => 'issued',
            'decided_by' => $this->admin->id,
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/decisions/{$decision->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('disciplinary_decisions', [
            'id' => $decision->id,
            'status' => 'final',
        ]);
    }

    public function test_cannot_approve_non_issued_decision(): void
    {
        $violation = Violation::create([
            'id' => Str::uuid(),
            'violation_number' => 'VIO-TEST-00005',
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
            'violation_date' => now(),
            'description' => 'قرار مُعتمد مسبقاً',
            'occurrence_number' => 1,
            'status' => 'decided',
            'reported_by' => $this->admin->id,
        ]);

        $decision = DisciplinaryDecision::create([
            'id' => Str::uuid(),
            'decision_number' => 'DEC-TEST-00002',
            'violation_id' => $violation->id,
            'employee_id' => $this->employee->id,
            'penalty_type' => 'written_warning',
            'penalty_type_ar' => 'إنذار كتابي',
            'effective_date' => now(),
            'justification' => 'بناءً على التحقيق',
            'status' => 'final',
            'decided_by' => $this->admin->id,
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/decisions/{$decision->id}/approve");

        $response->assertUnprocessable();
    }

    public function test_regular_user_cannot_create_violation(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/violations', [
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
            'violation_date' => now()->format('Y-m-d'),
            'description' => 'محاولة غير مصرح بها',
        ]);

        $response->assertForbidden();
    }

    public function test_penalty_suggestion_increments_with_occurrences(): void
    {
        // Create a closed violation for the employee
        Violation::create([
            'id' => Str::uuid(),
            'violation_number' => 'VIO-PREV-001',
            'employee_id' => $this->employee->id,
            'violation_type_id' => $this->violationType->id,
            'violation_date' => now()->subMonth(),
            'description' => 'مخالفة سابقة',
            'occurrence_number' => 1,
            'status' => 'closed',
            'reported_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            "/api/violation-types/{$this->violationType->id}/suggest-penalty?employee_id={$this->employee->id}"
        );

        $response->assertOk()
            ->assertJsonPath('data.occurrence_number', 2)
            ->assertJsonPath('data.suggested_penalty.penalty', 'written_warning');
    }
}
