<?php

namespace Tests\Feature;

use App\Models\AiPrediction;
use App\Models\AiRecommendation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_dashboard_requires_admin(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/ai/dashboard');
        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/ai/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'active_predictions',
                    'pending_recommendations',
                    'high_risk_employees',
                    'recent_analyses',
                    'summary',
                ],
            ]);
    }

    public function test_admin_can_analyze_leave_patterns(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/ai/analyze/leave-patterns');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['analysis_id', 'results', 'processing_time_ms'],
            ]);
    }

    public function test_admin_can_analyze_turnover_risk(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/ai/analyze/turnover-risk');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['analysis_id', 'summary', 'processing_time_ms'],
            ]);
    }

    public function test_admin_can_list_predictions(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/ai/predictions');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_list_recommendations(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/ai/recommendations');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_list_risk_scores(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/ai/risk-scores');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_list_analysis_logs(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/ai/analysis-logs');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_unauthenticated_cannot_access_ai(): void
    {
        $response = $this->getJson('/api/ai/dashboard');
        $response->assertUnauthorized();
    }
}
