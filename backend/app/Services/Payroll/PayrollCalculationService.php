<?php

namespace App\Services\Payroll;

use App\Models\HR\Employee;
use App\Models\Payroll\EmployeeLoan;
use App\Models\Payroll\Payroll;
use App\Models\Payroll\PayrollItem;
use App\Models\Payroll\PayrollSettings;
use App\Models\Roster\RosterAssignment;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollCalculationService
{
    protected array $settings;

    public function __construct()
    {
        $this->settings = PayrollSettings::getAllSettings();
    }

    /**
     * إنشاء مسير رواتب لشهر معين
     */
    public function generateMonthlyPayroll(int $year, int $month, string $createdBy): array
    {
        $employees = Employee::where('is_active', true)
            ->whereHas('currentContract', fn($q) => $q->where('is_active', true))
            ->get();

        $payrolls = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $payrolls[] = $this->calculateEmployeePayroll($employee, $year, $month, $createdBy);
            } catch (Exception $e) {
                $errors[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => count($payrolls),
            'errors' => $errors,
            'payrolls' => $payrolls,
        ];
    }

    /**
     * حساب راتب موظف واحد
     */
    public function calculateEmployeePayroll(
        Employee $employee,
        int $year,
        int $month,
        string $createdBy
    ): Payroll {
        // التحقق من عدم وجود مسير سابق
        $existing = Payroll::forEmployee($employee->id)
            ->forPeriod($year, $month)
            ->whereNotIn('status', [Payroll::STATUS_CANCELLED])
            ->first();

        if ($existing) {
            throw new Exception("يوجد مسير راتب للموظف {$employee->full_name} لهذا الشهر");
        }

        $contract = $employee->currentContract;
        if (!$contract) {
            throw new Exception("الموظف {$employee->full_name} ليس لديه عقد نشط");
        }

        return DB::transaction(function () use ($employee, $contract, $year, $month, $createdBy) {
            // إنشاء مسير الراتب
            $payroll = Payroll::create([
                'payroll_number' => $this->generatePayrollNumber($year, $month),
                'employee_id' => $employee->id,
                'period_year' => $year,
                'period_month' => $month,
                'status' => Payroll::STATUS_DRAFT,
                'currency' => $this->settings['default_currency'] ?? 'SAR',
                'bank_name' => $employee->bank_name,
                'bank_code' => $employee->bank_code,
                'iban' => $employee->iban,
            ]);

            // 1. الراتب الأساسي والبدلات
            $this->calculateBasicSalary($payroll, $contract);

            // 2. الوقت الإضافي
            $this->calculateOvertime($payroll, $employee, $year, $month);

            // 3. الخصومات (غياب، تأخير)
            $this->calculateAbsenceDeductions($payroll, $employee, $year, $month);

            // 4. التأمينات الاجتماعية (GOSI)
            $this->calculateGOSI($payroll, $employee);

            // 5. أقساط السلف
            $this->calculateLoanDeductions($payroll, $employee);

            // 6. إعادة حساب الإجماليات
            $payroll->recalculate();
            $payroll->status = Payroll::STATUS_CALCULATED;
            $payroll->calculated_by = $createdBy;
            $payroll->calculated_at = now();
            $payroll->save();

            return $payroll->fresh(['items', 'employee']);
        });
    }

    /**
     * حساب الراتب الأساسي والبدلات
     */
    protected function calculateBasicSalary(Payroll $payroll, $contract): void
    {
        $payroll->basic_salary = $contract->basic_salary;
        $payroll->housing_allowance = $contract->housing_allowance ?? 0;
        $payroll->transportation_allowance = $contract->transportation_allowance ?? 0;
        $payroll->other_allowances = $contract->other_allowances ?? 0;

        // إنشاء بنود الراتب
        PayrollItem::createEarning($payroll->id, PayrollItem::CODE_BASIC_SALARY, $payroll->basic_salary);

        if ($payroll->housing_allowance > 0) {
            PayrollItem::createEarning($payroll->id, PayrollItem::CODE_HOUSING, $payroll->housing_allowance);
        }

        if ($payroll->transportation_allowance > 0) {
            PayrollItem::createEarning($payroll->id, PayrollItem::CODE_TRANSPORTATION, $payroll->transportation_allowance);
        }
    }

    /**
     * حساب الوقت الإضافي
     */
    protected function calculateOvertime(Payroll $payroll, Employee $employee, int $year, int $month): void
    {
        // جلب ساعات العمل الإضافي من الجدولة
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $totalOvertimeHours = RosterAssignment::forEmployee($employee->id)
            ->inDateRange($startDate, $endDate)
            ->where('is_overtime', true)
            ->sum('overtime_hours');

        if ($totalOvertimeHours > 0) {
            // حساب سعر الساعة
            $dailySalary = ($payroll->basic_salary + $payroll->housing_allowance) / $this->settings['working_days_per_month'];
            $hourlyRate = $dailySalary / $this->settings['working_hours_per_day'];
            $overtimeRate = $hourlyRate * $this->settings['overtime_rate_normal'];

            $payroll->overtime_hours = $totalOvertimeHours;
            $payroll->overtime_rate = $overtimeRate;
            $payroll->overtime_amount = round($totalOvertimeHours * $overtimeRate, 2);

            PayrollItem::createEarning(
                $payroll->id,
                PayrollItem::CODE_OVERTIME,
                $payroll->overtime_amount,
                "{$totalOvertimeHours} ساعة × {$overtimeRate} ريال",
                $totalOvertimeHours,
                $overtimeRate
            );
        }
    }

    /**
     * حساب خصومات الغياب والتأخير
     */
    protected function calculateAbsenceDeductions(Payroll $payroll, Employee $employee, int $year, int $month): void
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // أيام الغياب
        $absenceDays = RosterAssignment::forEmployee($employee->id)
            ->inDateRange($startDate, $endDate)
            ->where('status', RosterAssignment::STATUS_ABSENT)
            ->count();

        if ($absenceDays > 0) {
            $dailySalary = ($payroll->basic_salary + $payroll->housing_allowance) / $this->settings['working_days_per_month'];
            $absenceDeduction = round($absenceDays * $dailySalary, 2);

            $payroll->absence_days = $absenceDays;
            $payroll->absence_deduction = $absenceDeduction;

            PayrollItem::createDeduction(
                $payroll->id,
                PayrollItem::CODE_ABSENCE,
                $absenceDeduction,
                "{$absenceDays} يوم غياب"
            );
        }

        // دقائق التأخير
        $lateMinutes = RosterAssignment::forEmployee($employee->id)
            ->inDateRange($startDate, $endDate)
            ->whereNotNull('actual_start')
            ->get()
            ->sum('late_minutes');

        // خصم التأخير بعد دقائق السماح
        $graceMinutes = $this->settings['late_grace_minutes'] *
            RosterAssignment::forEmployee($employee->id)->inDateRange($startDate, $endDate)->count();

        $chargeableMinutes = max(0, $lateMinutes - $graceMinutes);

        if ($chargeableMinutes > 0 && $this->settings['late_deduction_per_minute'] > 0) {
            $lateDeduction = round($chargeableMinutes * $this->settings['late_deduction_per_minute'], 2);

            $payroll->late_minutes = $chargeableMinutes;
            $payroll->late_deduction = $lateDeduction;

            PayrollItem::createDeduction(
                $payroll->id,
                PayrollItem::CODE_LATE,
                $lateDeduction,
                "{$chargeableMinutes} دقيقة تأخير"
            );
        }
    }

    /**
     * حساب التأمينات الاجتماعية (GOSI)
     */
    protected function calculateGOSI(Payroll $payroll, Employee $employee): void
    {
        // الراتب الخاضع للتأمينات (الأساسي + السكن)
        $gosiSalary = min(
            $payroll->basic_salary + $payroll->housing_allowance,
            $this->settings['gosi_max_salary']
        );

        // تحديد النسب حسب الجنسية
        $isSaudi = $employee->nationality === 'SA' || $employee->nationality === 'Saudi';

        if ($isSaudi) {
            $employeeRate = $this->settings['gosi_employee_rate'] / 100;
            $employerRate = $this->settings['gosi_employer_rate'] / 100;
        } else {
            $employeeRate = $this->settings['gosi_expat_employee_rate'] / 100;
            $employerRate = $this->settings['gosi_expat_employer_rate'] / 100;
        }

        $payroll->gosi_employee = round($gosiSalary * $employeeRate, 2);
        $payroll->gosi_employer = round($gosiSalary * $employerRate, 2);

        if ($payroll->gosi_employee > 0) {
            PayrollItem::createDeduction(
                $payroll->id,
                PayrollItem::CODE_GOSI,
                $payroll->gosi_employee,
                "التأمينات الاجتماعية ({$this->settings['gosi_employee_rate']}%)"
            );
        }
    }

    /**
     * حساب أقساط السلف
     */
    protected function calculateLoanDeductions(Payroll $payroll, Employee $employee): void
    {
        $activeLoans = EmployeeLoan::forEmployee($employee->id)
            ->active()
            ->where('remaining_amount', '>', 0)
            ->get();

        $totalLoanDeduction = 0;

        foreach ($activeLoans as $loan) {
            $deduction = min($loan->installment_amount, $loan->remaining_amount);
            $totalLoanDeduction += $deduction;

            $code = $loan->type === EmployeeLoan::TYPE_LOAN
                ? PayrollItem::CODE_LOAN
                : PayrollItem::CODE_ADVANCE;

            PayrollItem::createDeduction(
                $payroll->id,
                $code,
                $deduction,
                "قسط {$loan->loan_number} ({$loan->paid_installments + 1}/{$loan->total_installments})",
                'loan',
                $loan->id
            );
        }

        $payroll->loan_deduction = $totalLoanDeduction;
    }

    /**
     * حساب مكافأة نهاية الخدمة
     */
    public function calculateEndOfService(Employee $employee): array
    {
        $contract = $employee->currentContract;
        if (!$contract) {
            return ['amount' => 0, 'details' => 'لا يوجد عقد نشط'];
        }

        $years = $employee->service_years;
        $months = $employee->service_months % 12;
        $totalMonths = ($years * 12) + $months;

        $basicWithHousing = $contract->basic_salary + ($contract->housing_allowance ?? 0);

        $eosAmount = 0;
        $details = [];

        if ($totalMonths < 24) {
            // أقل من سنتين - لا يستحق
            $details[] = 'أقل من سنتين خدمة - لا يستحق مكافأة';
        } else {
            // أول 5 سنوات - نصف شهر عن كل سنة
            $first5Years = min($years, 5);
            $first5Amount = $first5Years * ($basicWithHousing * $this->settings['eos_first_5_years_rate']);
            $eosAmount += $first5Amount;
            $details[] = "أول {$first5Years} سنوات: " . number_format($first5Amount, 2) . ' ريال';

            // ما بعد 5 سنوات - شهر كامل عن كل سنة
            if ($years > 5) {
                $after5Years = $years - 5;
                $after5Amount = $after5Years * ($basicWithHousing * $this->settings['eos_after_5_years_rate']);
                $eosAmount += $after5Amount;
                $details[] = "بعد 5 سنوات ({$after5Years} سنة): " . number_format($after5Amount, 2) . ' ريال';
            }

            // الأشهر الكسرية
            if ($months > 0) {
                $monthlyRate = $years > 5
                    ? $basicWithHousing * $this->settings['eos_after_5_years_rate']
                    : $basicWithHousing * $this->settings['eos_first_5_years_rate'];
                $fractionAmount = ($months / 12) * $monthlyRate;
                $eosAmount += $fractionAmount;
                $details[] = "الأشهر الكسرية ({$months} شهر): " . number_format($fractionAmount, 2) . ' ريال';
            }
        }

        return [
            'years' => $years,
            'months' => $months,
            'basic_with_housing' => $basicWithHousing,
            'amount' => round($eosAmount, 2),
            'details' => $details,
        ];
    }

    /**
     * توليد رقم مسير الراتب
     */
    protected function generatePayrollNumber(int $year, int $month): string
    {
        $prefix = 'PAY';
        $period = $year . str_pad($month, 2, '0', STR_PAD_LEFT);
        $count = Payroll::forPeriod($year, $month)->count() + 1;

        return $prefix . $period . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * إعادة حساب مسير راتب
     */
    public function recalculatePayroll(Payroll $payroll, string $userId): Payroll
    {
        if ($payroll->status === Payroll::STATUS_PAID) {
            throw new Exception('لا يمكن إعادة حساب مسير مدفوع');
        }

        // حذف البنود القديمة
        $payroll->items()->delete();

        // إعادة الحساب
        $employee = $payroll->employee;
        $contract = $employee->currentContract;

        $this->calculateBasicSalary($payroll, $contract);
        $this->calculateOvertime($payroll, $employee, $payroll->period_year, $payroll->period_month);
        $this->calculateAbsenceDeductions($payroll, $employee, $payroll->period_year, $payroll->period_month);
        $this->calculateGOSI($payroll, $employee);
        $this->calculateLoanDeductions($payroll, $employee);

        $payroll->recalculate();
        $payroll->status = Payroll::STATUS_CALCULATED;
        $payroll->calculated_by = $userId;
        $payroll->calculated_at = now();
        $payroll->save();

        return $payroll->fresh(['items']);
    }
}
