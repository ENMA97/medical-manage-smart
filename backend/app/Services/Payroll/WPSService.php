<?php

namespace App\Services\Payroll;

use App\Models\Payroll\Payroll;
use App\Models\Payroll\PayrollSettings;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * خدمة نظام حماية الأجور (WPS)
 * Wage Protection System Service for Mudad
 */
class WPSService
{
    protected string $employerId;
    protected string $molId;

    public function __construct()
    {
        $this->employerId = PayrollSettings::getValue('wps_employer_id', '');
        $this->molId = PayrollSettings::getValue('wps_mol_id', '');
    }

    /**
     * توليد ملف WPS لمجموعة من مسيرات الرواتب
     *
     * @param array $payrollIds
     * @param string $generatedBy
     * @return array
     */
    public function generateWPSFile(array $payrollIds, string $generatedBy): array
    {
        if (empty($this->employerId) || empty($this->molId)) {
            throw new Exception('يجب تعبئة بيانات المنشأة في نظام حماية الأجور أولاً');
        }

        $payrolls = Payroll::whereIn('id', $payrollIds)
            ->where('status', Payroll::STATUS_APPROVED)
            ->with('employee')
            ->get();

        if ($payrolls->isEmpty()) {
            throw new Exception('لا توجد مسيرات رواتب معتمدة');
        }

        // التحقق من اكتمال البيانات
        $errors = $this->validatePayrollsForWPS($payrolls);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        // توليد محتوى الملف
        $content = $this->generateFileContent($payrolls);

        // حفظ الملف
        $fileName = $this->generateFileName($payrolls->first());
        $filePath = "wps/{$fileName}";

        Storage::put($filePath, $content);

        // تحديث مسيرات الرواتب
        foreach ($payrolls as $payroll) {
            $payroll->update([
                'wps_generated' => true,
                'wps_file_path' => $filePath,
                'wps_generated_at' => now(),
            ]);
        }

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'records_count' => $payrolls->count(),
            'total_amount' => $payrolls->sum('net_salary'),
        ];
    }

    /**
     * التحقق من صحة البيانات للـ WPS
     */
    protected function validatePayrollsForWPS($payrolls): array
    {
        $errors = [];

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;
            $employeeErrors = [];

            if (empty($employee->iban)) {
                $employeeErrors[] = 'رقم الآيبان مفقود';
            } elseif (!$this->validateIBAN($employee->iban)) {
                $employeeErrors[] = 'رقم الآيبان غير صحيح';
            }

            if (empty($employee->bank_code)) {
                $employeeErrors[] = 'رمز البنك مفقود';
            }

            if (empty($employee->id_number)) {
                $employeeErrors[] = 'رقم الهوية مفقود';
            }

            if (!empty($employeeErrors)) {
                $errors[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'errors' => $employeeErrors,
                ];
            }
        }

        return $errors;
    }

    /**
     * توليد محتوى ملف WPS (SIF Format)
     *
     * تنسيق الملف حسب معيار Mudad:
     * - Header Record
     * - Employee Records (EDR)
     * - Footer Record
     */
    protected function generateFileContent($payrolls): string
    {
        $lines = [];

        // Header Record (HDR)
        $lines[] = $this->generateHeaderRecord($payrolls);

        // Employee Detail Records (EDR)
        $sequenceNo = 1;
        foreach ($payrolls as $payroll) {
            $lines[] = $this->generateEmployeeRecord($payroll, $sequenceNo);
            $sequenceNo++;
        }

        // Footer Record (FTR)
        $lines[] = $this->generateFooterRecord($payrolls);

        return implode("\r\n", $lines);
    }

    /**
     * توليد سجل الرأس (Header)
     */
    protected function generateHeaderRecord($payrolls): string
    {
        $firstPayroll = $payrolls->first();
        $paymentDate = now()->format('Ymd');
        $paymentTime = now()->format('His');

        return implode(',', [
            'HDR',                                          // نوع السجل
            $this->employerId,                              // رقم المنشأة
            $this->molId,                                   // رقم وزارة العمل
            $paymentDate,                                   // تاريخ الدفع
            $paymentTime,                                   // وقت الدفع
            $firstPayroll->period_year,                     // سنة الراتب
            str_pad($firstPayroll->period_month, 2, '0', STR_PAD_LEFT), // شهر الراتب
            $payrolls->count(),                             // عدد الموظفين
            number_format($payrolls->sum('net_salary'), 2, '.', ''), // إجمالي المبلغ
            'SAR',                                          // العملة
        ]);
    }

    /**
     * توليد سجل الموظف
     */
    protected function generateEmployeeRecord(Payroll $payroll, int $sequenceNo): string
    {
        $employee = $payroll->employee;

        return implode(',', [
            'EDR',                                          // نوع السجل
            str_pad($sequenceNo, 6, '0', STR_PAD_LEFT),    // رقم التسلسل
            $employee->id_number,                           // رقم الهوية
            $this->getIdType($employee),                   // نوع الهوية
            $employee->bank_code,                           // رمز البنك
            $employee->iban,                                // رقم الآيبان
            number_format($payroll->net_salary, 2, '.', ''), // صافي الراتب
            number_format($payroll->basic_salary, 2, '.', ''), // الراتب الأساسي
            number_format($payroll->housing_allowance, 2, '.', ''), // بدل السكن
            number_format($payroll->other_allowances + $payroll->transportation_allowance, 2, '.', ''), // بدلات أخرى
            number_format($payroll->total_deductions, 2, '.', ''), // إجمالي الخصومات
            $payroll->pay_date?->format('Ymd') ?? now()->format('Ymd'), // تاريخ الدفع
            '', // ملاحظات
        ]);
    }

    /**
     * توليد سجل التذييل (Footer)
     */
    protected function generateFooterRecord($payrolls): string
    {
        return implode(',', [
            'FTR',                                          // نوع السجل
            $payrolls->count(),                             // عدد السجلات
            number_format($payrolls->sum('net_salary'), 2, '.', ''), // إجمالي المبلغ
            now()->format('YmdHis'),                       // الختم الزمني
        ]);
    }

    /**
     * تحديد نوع الهوية
     */
    protected function getIdType($employee): string
    {
        $nationality = strtoupper($employee->nationality ?? '');

        if ($nationality === 'SA' || $nationality === 'SAUDI') {
            return 'N'; // National ID (هوية وطنية)
        }

        return 'I'; // Iqama (إقامة)
    }

    /**
     * التحقق من صحة رقم الآيبان السعودي
     */
    protected function validateIBAN(string $iban): bool
    {
        // تنظيف الآيبان
        $iban = strtoupper(str_replace(' ', '', $iban));

        // التحقق من الطول (24 حرف للآيبان السعودي)
        if (strlen($iban) !== 24) {
            return false;
        }

        // التحقق من البداية SA
        if (substr($iban, 0, 2) !== 'SA') {
            return false;
        }

        // التحقق من أن الباقي أرقام
        if (!ctype_alnum($iban)) {
            return false;
        }

        return true;
    }

    /**
     * توليد اسم الملف
     */
    protected function generateFileName(Payroll $payroll): string
    {
        $period = $payroll->period_year . str_pad($payroll->period_month, 2, '0', STR_PAD_LEFT);
        $timestamp = now()->format('YmdHis');

        return "WPS_{$this->employerId}_{$period}_{$timestamp}.sif";
    }

    /**
     * الحصول على ملخص WPS لفترة معينة
     */
    public function getWPSSummary(int $year, int $month): array
    {
        $payrolls = Payroll::forPeriod($year, $month)
            ->whereIn('status', [Payroll::STATUS_APPROVED, Payroll::STATUS_PAID])
            ->with('employee')
            ->get();

        $wpsGenerated = $payrolls->where('wps_generated', true);
        $pending = $payrolls->where('wps_generated', false);

        return [
            'period' => "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT),
            'total_payrolls' => $payrolls->count(),
            'total_amount' => $payrolls->sum('net_salary'),
            'wps_generated' => [
                'count' => $wpsGenerated->count(),
                'amount' => $wpsGenerated->sum('net_salary'),
            ],
            'pending' => [
                'count' => $pending->count(),
                'amount' => $pending->sum('net_salary'),
            ],
        ];
    }

    /**
     * قائمة البنوك السعودية
     */
    public static function getSaudiBanks(): array
    {
        return [
            '10' => 'البنك المركزي السعودي (ساما)',
            '15' => 'بنك البلاد',
            '20' => 'بنك الرياض',
            '30' => 'البنك السعودي الفرنسي',
            '40' => 'البنك السعودي البريطاني (ساب)',
            '45' => 'البنك السعودي للاستثمار',
            '50' => 'البنك الأهلي السعودي',
            '55' => 'بنك الراجحي',
            '60' => 'بنك الجزيرة',
            '65' => 'مصرف الإنماء',
            '76' => 'البنك العربي الوطني',
            '80' => 'بنك الخليج الدولي',
        ];
    }
}
