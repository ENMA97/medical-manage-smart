<?php

namespace Tests\Unit\Services\Leave;

use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveRequest;
use App\Models\Leave\LeaveType;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveRequestService();
    }

    /** @test */
    public function it_can_create_leave_request()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create([
            'default_days' => 21,
            'requires_attachment' => false,
        ]);

        $employeeId = fake()->uuid();

        LeaveBalance::factory()->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y'),
            'remaining_days' => 21,
        ]);

        $data = [
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(10)->format('Y-m-d'),
            'reason' => 'إجازة سنوية',
        ];

        // Act
        $request = $this->service->createRequest($data, $employeeId);

        // Assert
        $this->assertInstanceOf(LeaveRequest::class, $request);
        $this->assertEquals('draft', $request->status);
        $this->assertEquals($employeeId, $request->employee_id);
        $this->assertNotNull($request->request_number);
    }

    /** @test */
    public function it_calculates_working_days_correctly()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create();
        $employeeId = fake()->uuid();

        LeaveBalance::factory()->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y'),
            'remaining_days' => 21,
        ]);

        // Monday to Friday (5 working days)
        $startDate = now()->next('Monday')->format('Y-m-d');
        $endDate = now()->next('Monday')->addDays(4)->format('Y-m-d');

        $data = [
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'test',
        ];

        // Act
        $request = $this->service->createRequest($data, $employeeId);

        // Assert
        $this->assertEquals(5, $request->working_days);
    }

    /** @test */
    public function it_prevents_request_exceeding_balance()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create();
        $employeeId = fake()->uuid();

        LeaveBalance::factory()->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y'),
            'remaining_days' => 3,
        ]);

        $data = [
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(17)->format('Y-m-d'), // ~10 days
            'reason' => 'test',
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('الرصيد المتبقي غير كافي');

        $this->service->createRequest($data, $employeeId);
    }

    /** @test */
    public function it_can_submit_draft_request()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'draft',
        ]);

        // Act
        $submittedRequest = $this->service->submitRequest($request->id);

        // Assert
        $this->assertEquals('pending_supervisor', $submittedRequest->status);
        $this->assertNotNull($submittedRequest->submitted_at);
    }

    /** @test */
    public function it_prevents_submitting_non_draft_request()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'pending_supervisor',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);

        $this->service->submitRequest($request->id);
    }

    /** @test */
    public function it_can_process_supervisor_recommendation()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'pending_supervisor',
        ]);

        $supervisorId = fake()->uuid();
        $delegateId = fake()->uuid();

        // Act
        $updatedRequest = $this->service->processSupervisorRecommendation(
            $request->id,
            $supervisorId,
            'recommend',
            'موافق على الطلب',
            ['task1', 'task2'],
            $delegateId
        );

        // Assert
        $this->assertEquals('pending_admin_manager', $updatedRequest->status);
        $this->assertEquals($delegateId, $updatedRequest->delegate_id);

        $this->assertDatabaseHas('leave_approvals', [
            'leave_request_id' => $request->id,
            'approver_id' => $supervisorId,
            'action' => 'recommend',
            'approval_type' => 'recommendation',
        ]);
    }

    /** @test */
    public function it_rejects_request_on_supervisor_rejection()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'pending_supervisor',
        ]);

        // Act
        $updatedRequest = $this->service->processSupervisorRecommendation(
            $request->id,
            fake()->uuid(),
            'reject',
            'لا يمكن الموافقة بسبب ضغط العمل'
        );

        // Assert
        $this->assertEquals('rejected', $updatedRequest->status);
    }

    /** @test */
    public function it_can_process_admin_manager_approval()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'pending_admin_manager',
        ]);

        // Act
        $updatedRequest = $this->service->processAdminManagerApproval(
            $request->id,
            fake()->uuid(),
            'approve',
            'موافق'
        );

        // Assert
        $this->assertEquals('pending_hr', $updatedRequest->status);

        $this->assertDatabaseHas('leave_approvals', [
            'leave_request_id' => $request->id,
            'action' => 'approve',
            'approval_type' => 'approval',
        ]);
    }

    /** @test */
    public function it_can_process_hr_endorsement()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'pending_hr',
            'delegate_id' => fake()->uuid(),
        ]);

        // Act
        $updatedRequest = $this->service->processHrEndorsement(
            $request->id,
            fake()->uuid(),
            'endorse',
            'تم التحقق من الرصيد'
        );

        // Assert
        $this->assertEquals('pending_delegate', $updatedRequest->status);

        $this->assertDatabaseHas('leave_approvals', [
            'leave_request_id' => $request->id,
            'action' => 'endorse',
            'approval_type' => 'endorsement',
        ]);
    }

    /** @test */
    public function it_completes_form_on_delegate_confirmation()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'pending_delegate',
            'delegate_id' => fake()->uuid(),
        ]);

        // Act
        $updatedRequest = $this->service->processDelegateConfirmation(
            $request->id,
            $request->delegate_id,
            'confirm',
            'أؤكد استلام المهام'
        );

        // Assert
        $this->assertEquals('form_completed', $updatedRequest->status);

        $this->assertDatabaseHas('leave_approvals', [
            'leave_request_id' => $request->id,
            'action' => 'confirm',
            'approval_type' => 'coverage_confirmation',
        ]);
    }

    /** @test */
    public function it_can_cancel_draft_request()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'draft',
        ]);

        // Act
        $cancelledRequest = $this->service->cancelRequest(
            $request->id,
            'لم أعد بحاجة للإجازة',
            $request->employee_id
        );

        // Assert
        $this->assertEquals('cancelled', $cancelledRequest->status);
        $this->assertNotNull($cancelledRequest->cancellation_reason);
    }

    /** @test */
    public function it_prevents_cancelling_approved_request()
    {
        // Arrange
        $request = LeaveRequest::factory()->create([
            'status' => 'approved',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('لا يمكن إلغاء طلب معتمد');

        $this->service->cancelRequest(
            $request->id,
            'test',
            $request->employee_id
        );
    }

    /** @test */
    public function it_validates_advance_notice_requirement()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create([
            'advance_notice_days' => 14,
        ]);

        $employeeId = fake()->uuid();

        LeaveBalance::factory()->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y'),
            'remaining_days' => 21,
        ]);

        $data = [
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'), // Less than 14 days notice
            'end_date' => now()->addDays(8)->format('Y-m-d'),
            'reason' => 'test',
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('يجب تقديم الطلب قبل');

        $this->service->createRequest($data, $employeeId);
    }
}
