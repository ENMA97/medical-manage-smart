<?php

namespace Tests\Unit\Services\Payroll;

use App\Models\HR\Employee;
use App\Models\HR\Contract;
use App\Models\Payroll\Payroll;
use App\Models\Payroll\PayrollItem;
use App\Models\Payroll\EmployeeLoan;
use App\Services\Payroll\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PayrollCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PayrollCalculationService::class);
    }

    /** @test */
    public function it_generates_payroll_for_active_employees()
    {
        // Arrange
        $employee = Employee::factory()->create(['is_active' => true]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 5000,
            'housing_allowance' => 1000,
            'transport_allowance' => 500,
        ]);

        $year = date('Y');
        $month = date('m');
        $userId = fake()->uuid();

        // Act
        $result = $this->service->generateMonthlyPayroll($year, $month, $userId);

        // Assert
        $this->assertArrayHasKey('success', $result);
        $this->assertGreaterThanOrEqual(1, $result['success']);

        $payroll = Payroll::where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $this->assertNotNull($payroll);
        $this->assertEquals(5000, $payroll->basic_salary);
    }

    /** @test */
    public function it_calculates_gosi_deductions_correctly()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'is_active' => true,
            'nationality' => 'SA', // Saudi national
        ]);

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 10000,
            'housing_allowance' => 2500, // 25% of basic
        ]);

        $year = date('Y');
        $month = date('m');

        // Act
        $result = $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Assert
        $payroll = Payroll::where('employee_id', $employee->id)->first();

        // GOSI base = basic + housing = 10000 + 2500 = 12500
        // Employee GOSI (9.75%) = 12500 * 0.0975 = 1218.75
        // Employer GOSI (11.75%) = 12500 * 0.1175 = 1468.75
        $this->assertEquals(1218.75, $payroll->gosi_employee);
        $this->assertEquals(1468.75, $payroll->gosi_employer);
    }

    /** @test */
    public function it_skips_gosi_for_non_saudi_employees()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'is_active' => true,
            'nationality' => 'EG', // Non-Saudi
        ]);

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 10000,
        ]);

        $year = date('Y');
        $month = date('m');

        // Act
        $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Assert
        $payroll = Payroll::where('employee_id', $employee->id)->first();
        $this->assertEquals(0, $payroll->gosi_employee);
    }

    /** @test */
    public function it_deducts_active_loans()
    {
        // Arrange
        $employee = Employee::factory()->create(['is_active' => true]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 8000,
        ]);

        $loan = EmployeeLoan::factory()->create([
            'employee_id' => $employee->id,
            'status' => EmployeeLoan::STATUS_ACTIVE,
            'loan_amount' => 10000,
            'installment_amount' => 1000,
            'remaining_amount' => 8000,
        ]);

        $year = date('Y');
        $month = date('m');

        // Act
        $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Assert
        $payroll = Payroll::where('employee_id', $employee->id)->first();

        $loanDeduction = PayrollItem::where('payroll_id', $payroll->id)
            ->where('code', 'LOAN')
            ->first();

        $this->assertNotNull($loanDeduction);
        $this->assertEquals(1000, $loanDeduction->amount);
    }

    /** @test */
    public function it_calculates_overtime_correctly()
    {
        // Arrange
        $employee = Employee::factory()->create(['is_active' => true]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 6000,
        ]);

        // Create payroll with overtime data
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'year' => date('Y'),
            'month' => date('m'),
            'basic_salary' => 6000,
            'status' => Payroll::STATUS_DRAFT,
        ]);

        // Assume 10 hours overtime at 1.5x rate
        $hourlyRate = 6000 / (30 * 8); // Monthly / (days * hours)
        $overtimeAmount = 10 * $hourlyRate * 1.5;

        PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'type' => 'earning',
            'code' => 'OVERTIME',
            'name_ar' => 'عمل إضافي',
            'amount' => $overtimeAmount,
        ]);

        // Act
        $updatedPayroll = $this->service->recalculatePayroll($payroll, fake()->uuid());

        // Assert
        $this->assertEquals($overtimeAmount, $updatedPayroll->items->where('code', 'OVERTIME')->first()->amount);
    }

    /** @test */
    public function it_calculates_net_salary_correctly()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'is_active' => true,
            'nationality' => 'SA',
        ]);

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 5000,
            'housing_allowance' => 1250,
            'transport_allowance' => 500,
        ]);

        $year = date('Y');
        $month = date('m');

        // Act
        $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Assert
        $payroll = Payroll::where('employee_id', $employee->id)->first();

        // Gross = Basic + Housing + Transport = 5000 + 1250 + 500 = 6750
        // GOSI Employee = (5000 + 1250) * 0.0975 = 609.375
        // Net = 6750 - 609.375 = 6140.625
        $expectedNet = 6750 - (6250 * 0.0975);
        $this->assertEquals(round($expectedNet, 2), round($payroll->net_salary, 2));
    }

    /** @test */
    public function it_does_not_generate_duplicate_payrolls()
    {
        // Arrange
        $employee = Employee::factory()->create(['is_active' => true]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 5000,
        ]);

        $year = date('Y');
        $month = date('m');

        // First generation
        $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Act - Try to generate again
        $result = $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Assert - Should skip already existing
        $count = Payroll::where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('month', $month)
            ->count();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_handles_percentage_based_contract()
    {
        // Arrange
        $employee = Employee::factory()->create(['is_active' => true]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'type' => 'percentage',
            'basic_salary' => 0,
            'commission_rate' => 10, // 10% commission
        ]);

        $year = date('Y');
        $month = date('m');

        // Act
        $result = $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Assert - Percentage contracts should be generated but with 0 basic
        $payroll = Payroll::where('employee_id', $employee->id)->first();
        $this->assertNotNull($payroll);
        $this->assertEquals(0, $payroll->basic_salary);
    }

    /** @test */
    public function it_skips_inactive_employees()
    {
        // Arrange
        $inactiveEmployee = Employee::factory()->create(['is_active' => false]);
        Contract::factory()->create([
            'employee_id' => $inactiveEmployee->id,
            'is_active' => true,
            'basic_salary' => 5000,
        ]);

        $year = date('Y');
        $month = date('m');

        // Act
        $result = $this->service->generateMonthlyPayroll($year, $month, fake()->uuid());

        // Assert
        $payroll = Payroll::where('employee_id', $inactiveEmployee->id)->first();
        $this->assertNull($payroll);
    }

    /** @test */
    public function it_can_recalculate_payroll()
    {
        // Arrange
        $employee = Employee::factory()->create(['is_active' => true]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'is_active' => true,
            'basic_salary' => 5000,
        ]);

        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'year' => date('Y'),
            'month' => date('m'),
            'basic_salary' => 4000, // Old value
            'net_salary' => 4000,
            'status' => Payroll::STATUS_DRAFT,
        ]);

        // Act
        $recalculated = $this->service->recalculatePayroll($payroll, fake()->uuid());

        // Assert
        $this->assertEquals(5000, $recalculated->basic_salary);
        $this->assertNotEquals(4000, $recalculated->net_salary);
    }
}
