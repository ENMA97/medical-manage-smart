<?php

namespace Tests\Feature\Api\Finance;

use App\Models\Finance\InsuranceClaim;
use App\Models\Finance\InsuranceCompany;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InsuranceClaimTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected InsuranceCompany $insuranceCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->insuranceCompany = InsuranceCompany::factory()->create([
            'is_active' => true,
        ]);

        Gate::define('finance.view', fn() => true);
        Gate::define('finance.manage', fn() => true);
        Gate::define('finance.claims.approve', fn() => true);
    }

    /** @test */
    public function can_list_insurance_claims()
    {
        // Arrange
        InsuranceClaim::factory()->count(5)->create([
            'insurance_company_id' => $this->insuranceCompany->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/finance/claims');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'claim_number',
                            'status',
                            'claimed_amount',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_create_insurance_claim()
    {
        // Arrange
        $claimData = [
            'insurance_company_id' => $this->insuranceCompany->id,
            'patient_name' => 'أحمد محمد',
            'patient_id_number' => '1234567890',
            'service_date' => now()->toDateString(),
            'claimed_amount' => 5000.00,
            'services' => [
                [
                    'service_code' => 'CONS001',
                    'service_name' => 'استشارة طبية',
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/finance/claims', $claimData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('insurance_claims', [
            'insurance_company_id' => $this->insuranceCompany->id,
            'patient_name' => 'أحمد محمد',
        ]);
    }

    /** @test */
    public function can_view_single_claim()
    {
        // Arrange
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/finance/claims/{$claim->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $claim->id,
                ],
            ]);
    }

    /** @test */
    public function can_scrub_claim()
    {
        // Arrange
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'submitted',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/finance/claims/{$claim->id}/scrub", [
                'scrub_notes' => 'تم التحقق من البيانات',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $claim->refresh();
        $this->assertEquals('scrubbed', $claim->status);
    }

    /** @test */
    public function can_submit_claim()
    {
        // Arrange
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'scrubbed',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/finance/claims/{$claim->id}/submit");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $claim->refresh();
        $this->assertNotNull($claim->submission_date);
    }

    /** @test */
    public function can_approve_claim()
    {
        // Arrange
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'submitted',
            'claimed_amount' => 5000,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/finance/claims/{$claim->id}/approve", [
                'approved_amount' => 4500,
                'approval_notes' => 'تمت الموافقة مع خصم',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $claim->refresh();
        $this->assertEquals('approved', $claim->status);
        $this->assertEquals(4500, $claim->approved_amount);
    }

    /** @test */
    public function can_reject_claim()
    {
        // Arrange
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'submitted',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/finance/claims/{$claim->id}/reject", [
                'rejection_reason' => 'بيانات غير مكتملة',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $claim->refresh();
        $this->assertEquals('rejected', $claim->status);
    }

    /** @test */
    public function can_mark_claim_as_paid()
    {
        // Arrange
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'approved',
            'approved_amount' => 4500,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/finance/claims/{$claim->id}/mark-paid", [
                'paid_amount' => 4500,
                'payment_reference' => 'PAY123456',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $claim->refresh();
        $this->assertEquals('paid', $claim->status);
        $this->assertEquals(4500, $claim->paid_amount);
    }

    /** @test */
    public function can_get_pending_claims()
    {
        // Arrange
        InsuranceClaim::factory()->count(3)->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'submitted',
        ]);

        InsuranceClaim::factory()->count(2)->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'approved',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/finance/claims/pending');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }

    /** @test */
    public function can_get_aging_report()
    {
        // Arrange
        InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'approved',
            'submission_date' => now()->subDays(45),
            'approved_amount' => 5000,
            'paid_amount' => 0,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/finance/claims/aging');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'buckets',
                    'total_outstanding',
                ],
            ]);
    }

    /** @test */
    public function cannot_approve_draft_claim()
    {
        // Arrange
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'draft', // Not submitted yet
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/finance/claims/{$claim->id}/approve", [
                'approved_amount' => 4500,
            ]);

        // Assert
        $response->assertStatus(400);
    }

    /** @test */
    public function create_claim_validates_required_fields()
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/finance/claims', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['insurance_company_id', 'claimed_amount']);
    }

    /** @test */
    public function can_filter_claims_by_status()
    {
        // Arrange
        InsuranceClaim::factory()->count(3)->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'approved',
        ]);

        InsuranceClaim::factory()->count(2)->create([
            'insurance_company_id' => $this->insuranceCompany->id,
            'status' => 'rejected',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/finance/claims?status=approved');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }
}
