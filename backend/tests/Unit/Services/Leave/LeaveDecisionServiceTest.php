<?php

namespace Tests\Unit\Services\Leave;

use App\Models\Leave\LeaveDecision;
use App\Models\Leave\LeaveRequest;
use App\Services\Leave\LeaveDecisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDecisionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveDecisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveDecisionService();
    }

    /** @test */
    public function it_can_create_decision_for_completed_form()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'form_completed',
        ]);

        // Act
        $decision = $this->service->createDecision($request->id, fake()->uuid());

        // Assert
        $this->assertInstanceOf(LeaveDecision::class, $decision);
        $this->assertEquals($request->id, $decision->leave_request_id);
        $this->assertNotNull($decision->decision_number);
        $this->assertStringStartsWith('pending_', $decision->status);
    }

    /** @test */
    public function it_routes_to_admin_manager_for_regular_employees()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'form_completed',
            'is_medical_staff' => false,
        ]);

        // Act
        $decision = $this->service->createDecision($request->id, fake()->uuid());

        // Assert
        $this->assertEquals('pending_admin_manager', $decision->status);
        $this->assertFalse($decision->requires_gm_approval);
    }

    /** @test */
    public function it_routes_to_medical_director_for_doctors()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'form_completed',
            'is_medical_staff' => true,
        ]);

        // Act
        $decision = $this->service->createDecision($request->id, fake()->uuid());

        // Assert
        $this->assertEquals('pending_medical_director', $decision->status);
    }

    /** @test */
    public function it_prevents_duplicate_decision()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'form_completed',
        ]);

        LeaveDecision::factory()->create([
            'leave_request_id' => $request->id,
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('يوجد قرار مسجل مسبقاً');

        $this->service->createDecision($request->id, fake()->uuid());
    }

    /** @test */
    public function admin_manager_can_approve_directly()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_admin_manager',
        ]);

        // Act
        $updatedDecision = $this->service->processAdminManagerAction(
            $decision->id,
            fake()->uuid(),
            'approve',
            'موافق على القرار'
        );

        // Assert
        $this->assertEquals('approved', $updatedDecision->status);
        $this->assertEquals('approve', $updatedDecision->admin_manager_action);
        $this->assertNotNull($updatedDecision->approved_at);
        $this->assertFalse($updatedDecision->forwarded_to_gm);
    }

    /** @test */
    public function admin_manager_can_forward_to_gm()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_admin_manager',
        ]);

        // Act
        $updatedDecision = $this->service->processAdminManagerAction(
            $decision->id,
            fake()->uuid(),
            'forward_to_gm',
            'يحتاج موافقة المدير العام'
        );

        // Assert
        $this->assertEquals('pending_general_manager', $updatedDecision->status);
        $this->assertEquals('forward_to_gm', $updatedDecision->admin_manager_action);
        $this->assertTrue($updatedDecision->forwarded_to_gm);
    }

    /** @test */
    public function admin_manager_can_reject()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_admin_manager',
        ]);

        // Act
        $updatedDecision = $this->service->processAdminManagerAction(
            $decision->id,
            fake()->uuid(),
            'reject',
            'مرفوض لعدم استيفاء الشروط'
        );

        // Assert
        $this->assertEquals('rejected', $updatedDecision->status);
        $this->assertEquals('reject', $updatedDecision->admin_manager_action);
    }

    /** @test */
    public function medical_director_can_approve_for_doctors()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_medical_director',
        ]);

        // Act
        $updatedDecision = $this->service->processMedicalDirectorAction(
            $decision->id,
            fake()->uuid(),
            'approve',
            'موافق'
        );

        // Assert
        $this->assertEquals('approved', $updatedDecision->status);
        $this->assertEquals('approve', $updatedDecision->medical_director_action);
    }

    /** @test */
    public function medical_director_can_forward_to_gm()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_medical_director',
        ]);

        // Act
        $updatedDecision = $this->service->processMedicalDirectorAction(
            $decision->id,
            fake()->uuid(),
            'forward_to_gm',
            'إحالة للمدير العام'
        );

        // Assert
        $this->assertEquals('pending_general_manager', $updatedDecision->status);
        $this->assertTrue($updatedDecision->forwarded_to_gm);
    }

    /** @test */
    public function general_manager_can_approve_forwarded_decision()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_general_manager',
            'forwarded_to_gm' => true,
        ]);

        // Act
        $updatedDecision = $this->service->processGeneralManagerAction(
            $decision->id,
            fake()->uuid(),
            'approve',
            'موافق'
        );

        // Assert
        $this->assertEquals('approved', $updatedDecision->status);
        $this->assertEquals('approve', $updatedDecision->gm_action);
        $this->assertNotNull($updatedDecision->gm_approved_at);
    }

    /** @test */
    public function general_manager_can_reject()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_general_manager',
            'forwarded_to_gm' => true,
        ]);

        // Act
        $updatedDecision = $this->service->processGeneralManagerAction(
            $decision->id,
            fake()->uuid(),
            'reject',
            'مرفوض'
        );

        // Assert
        $this->assertEquals('rejected', $updatedDecision->status);
        $this->assertEquals('reject', $updatedDecision->gm_action);
    }

    /** @test */
    public function it_updates_leave_request_status_on_approval()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'form_completed',
        ]);

        $decision = LeaveDecision::factory()->create([
            'leave_request_id' => $request->id,
            'status' => 'pending_admin_manager',
        ]);

        // Act
        $this->service->processAdminManagerAction(
            $decision->id,
            fake()->uuid(),
            'approve',
            'موافق'
        );

        // Assert
        $request->refresh();
        $this->assertEquals('approved', $request->status);
    }

    /** @test */
    public function it_updates_leave_request_status_on_rejection()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'form_completed',
        ]);

        $decision = LeaveDecision::factory()->create([
            'leave_request_id' => $request->id,
            'status' => 'pending_admin_manager',
        ]);

        // Act
        $this->service->processAdminManagerAction(
            $decision->id,
            fake()->uuid(),
            'reject',
            'مرفوض'
        );

        // Assert
        $request->refresh();
        $this->assertEquals('rejected', $request->status);
    }

    /** @test */
    public function it_prevents_action_on_wrong_status()
    {
        // Arrange
        $decision = LeaveDecision::factory()->create([
            'status' => 'pending_medical_director',
        ]);

        // Act & Assert - Admin Manager tries to act on Medical Director's decision
        $this->expectException(\Exception::class);

        $this->service->processAdminManagerAction(
            $decision->id,
            fake()->uuid(),
            'approve',
            'موافق'
        );
    }
}
