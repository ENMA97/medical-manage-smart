<?php

namespace Tests\Unit\Services\Leave;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveBalanceAdjustment;
use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveType;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveBalanceService();
    }

    /** @test */
    public function it_can_initialize_balance_for_employee()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create([
            'code' => 'ANNUAL',
            'default_days' => 21,
        ]);
        $employeeId = fake()->uuid();
        $year = date('Y');

        // Act
        $balance = $this->service->initializeBalance(
            $employeeId,
            $leaveType->id,
            $year,
            21,
            fake()->uuid()
        );

        // Assert
        $this->assertInstanceOf(LeaveBalance::class, $balance);
        $this->assertEquals($employeeId, $balance->employee_id);
        $this->assertEquals($leaveType->id, $balance->leave_type_id);
        $this->assertEquals($year, $balance->year);
        $this->assertEquals(21, $balance->entitled_days);
        $this->assertEquals(0, $balance->used_days);
        $this->assertEquals(21, $balance->remaining_days);
    }

    /** @test */
    public function it_prevents_duplicate_balance_initialization()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create();
        $employeeId = fake()->uuid();
        $year = date('Y');

        // Create existing balance
        LeaveBalance::factory()->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('يوجد رصيد مسجل مسبقاً');

        $this->service->initializeBalance(
            $employeeId,
            $leaveType->id,
            $year,
            21,
            fake()->uuid()
        );
    }

    /** @test */
    public function it_can_add_manual_adjustment()
    {
        // Arrange
        $balance = LeaveBalance::factory()->create([
            'entitled_days' => 21,
            'used_days' => 5,
            'remaining_days' => 16,
        ]);

        // Act - Add 3 days
        $updatedBalance = $this->service->addManualAdjustment(
            $balance->id,
            3,
            'تعديل إداري',
            fake()->uuid()
        );

        // Assert
        $this->assertEquals(19, $updatedBalance->remaining_days);
        $this->assertDatabaseHas('leave_balance_adjustments', [
            'leave_balance_id' => $balance->id,
            'adjustment_type' => 'manual',
            'days' => 3,
        ]);
    }

    /** @test */
    public function it_can_deduct_from_balance()
    {
        // Arrange
        $balance = LeaveBalance::factory()->create([
            'entitled_days' => 21,
            'used_days' => 5,
            'remaining_days' => 16,
        ]);

        // Act - Deduct 3 days
        $updatedBalance = $this->service->addManualAdjustment(
            $balance->id,
            -3,
            'خصم تأديبي',
            fake()->uuid()
        );

        // Assert
        $this->assertEquals(13, $updatedBalance->remaining_days);
    }

    /** @test */
    public function it_prevents_deduction_exceeding_remaining_balance()
    {
        // Arrange
        $balance = LeaveBalance::factory()->create([
            'entitled_days' => 21,
            'used_days' => 20,
            'remaining_days' => 1,
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('لا يمكن خصم أكثر من الرصيد المتبقي');

        $this->service->addManualAdjustment(
            $balance->id,
            -5,
            'خصم',
            fake()->uuid()
        );
    }

    /** @test */
    public function it_can_consume_balance_for_leave_request()
    {
        // Arrange
        $balance = LeaveBalance::factory()->create([
            'entitled_days' => 21,
            'used_days' => 0,
            'remaining_days' => 21,
        ]);
        $leaveRequestId = fake()->uuid();

        // Act
        $updatedBalance = $this->service->consumeBalance(
            $balance->id,
            5,
            $leaveRequestId,
            fake()->uuid()
        );

        // Assert
        $this->assertEquals(5, $updatedBalance->used_days);
        $this->assertEquals(16, $updatedBalance->remaining_days);
        $this->assertDatabaseHas('leave_balance_adjustments', [
            'leave_balance_id' => $balance->id,
            'adjustment_type' => 'consumption',
            'days' => -5,
            'leave_request_id' => $leaveRequestId,
        ]);
    }

    /** @test */
    public function it_can_restore_balance_after_cancellation()
    {
        // Arrange
        $balance = LeaveBalance::factory()->create([
            'entitled_days' => 21,
            'used_days' => 10,
            'remaining_days' => 11,
        ]);
        $leaveRequestId = fake()->uuid();

        // Act
        $updatedBalance = $this->service->restoreBalance(
            $balance->id,
            5,
            $leaveRequestId,
            fake()->uuid()
        );

        // Assert
        $this->assertEquals(5, $updatedBalance->used_days);
        $this->assertEquals(16, $updatedBalance->remaining_days);
        $this->assertDatabaseHas('leave_balance_adjustments', [
            'leave_balance_id' => $balance->id,
            'adjustment_type' => 'restoration',
            'days' => 5,
        ]);
    }

    /** @test */
    public function it_can_carry_over_balance_to_next_year()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create([
            'can_be_carried_over' => true,
            'max_carry_over_days' => 10,
        ]);

        $balance = LeaveBalance::factory()->create([
            'leave_type_id' => $leaveType->id,
            'year' => date('Y') - 1,
            'entitled_days' => 21,
            'used_days' => 5,
            'remaining_days' => 16,
        ]);

        // Act
        $newBalance = $this->service->carryOverBalance(
            $balance->employee_id,
            $leaveType->id,
            date('Y') - 1,
            fake()->uuid()
        );

        // Assert
        $this->assertNotNull($newBalance);
        $this->assertEquals(date('Y'), $newBalance->year);
        // Should carry over max 10 days (capped)
        $this->assertEquals(10, $newBalance->carried_over_days);
    }

    /** @test */
    public function it_returns_null_for_non_carryover_leave_type()
    {
        // Arrange
        $leaveType = LeaveType::factory()->create([
            'can_be_carried_over' => false,
        ]);

        $balance = LeaveBalance::factory()->create([
            'leave_type_id' => $leaveType->id,
            'year' => date('Y') - 1,
            'remaining_days' => 5,
        ]);

        // Act
        $result = $this->service->carryOverBalance(
            $balance->employee_id,
            $leaveType->id,
            date('Y') - 1,
            fake()->uuid()
        );

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_get_employee_balance_summary()
    {
        // Arrange
        $employeeId = fake()->uuid();
        $year = date('Y');

        $annualType = LeaveType::factory()->create(['code' => 'ANNUAL', 'name_ar' => 'سنوية']);
        $sickType = LeaveType::factory()->create(['code' => 'SICK', 'name_ar' => 'مرضية']);

        LeaveBalance::factory()->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $annualType->id,
            'year' => $year,
            'entitled_days' => 21,
            'used_days' => 5,
            'remaining_days' => 16,
        ]);

        LeaveBalance::factory()->create([
            'employee_id' => $employeeId,
            'leave_type_id' => $sickType->id,
            'year' => $year,
            'entitled_days' => 30,
            'used_days' => 2,
            'remaining_days' => 28,
        ]);

        // Act
        $summary = $this->service->getEmployeeBalanceSummary($employeeId, $year);

        // Assert
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('balances', $summary);
        $this->assertArrayHasKey('totals', $summary);
        $this->assertCount(2, $summary['balances']);
        $this->assertEquals(51, $summary['totals']['entitled']);
        $this->assertEquals(7, $summary['totals']['used']);
        $this->assertEquals(44, $summary['totals']['remaining']);
    }

    /** @test */
    public function it_can_correct_balance()
    {
        // Arrange
        $balance = LeaveBalance::factory()->create([
            'entitled_days' => 21,
            'used_days' => 10,
            'remaining_days' => 11,
        ]);

        // Act - Correct to 15 remaining days
        $updatedBalance = $this->service->correctBalance(
            $balance->id,
            15,
            'تصحيح خطأ إداري',
            fake()->uuid()
        );

        // Assert
        $this->assertEquals(15, $updatedBalance->remaining_days);
        $this->assertDatabaseHas('leave_balance_adjustments', [
            'leave_balance_id' => $balance->id,
            'adjustment_type' => 'correction',
            'days' => 4, // 15 - 11 = 4
        ]);
    }
}
