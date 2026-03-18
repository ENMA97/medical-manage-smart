<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly Payroll $payroll
    ) {}

    public function collection()
    {
        return $this->payroll->items()->with('employee')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'الرقم الوظيفي',
            'اسم الموظف',
            'الراتب الأساسي',
            'بدل السكن',
            'بدل النقل',
            'بدل الطعام',
            'بدل الهاتف',
            'بدلات أخرى',
            'إجمالي الراتب',
            'التأمينات (الموظف)',
            'التأمينات (صاحب العمل)',
            'إجمالي الخصومات',
            'صافي الراتب',
            'البنك',
            'آيبان',
        ];
    }

    public function map($item): array
    {
        static $counter = 0;
        $counter++;

        return [
            $counter,
            $item->employee?->employee_number ?? '—',
            $item->employee?->full_name_ar ?? $item->employee?->full_name_en ?? '—',
            number_format($item->basic_salary, 2),
            number_format($item->housing_allowance, 2),
            number_format($item->transport_allowance, 2),
            number_format($item->food_allowance ?? 0, 2),
            number_format($item->phone_allowance ?? 0, 2),
            number_format($item->other_allowances ?? 0, 2),
            number_format($item->gross_salary, 2),
            number_format($item->gosi_employee, 2),
            number_format($item->gosi_employer, 2),
            number_format($item->total_deductions, 2),
            number_format($item->net_salary, 2),
            $item->bank_name ?? '—',
            $item->iban ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];
    }
}
