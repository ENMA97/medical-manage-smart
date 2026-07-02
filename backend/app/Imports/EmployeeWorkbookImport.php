<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;

/**
 * استيراد ملف إكسل متعدد الأوراق
 * - ورقة 1: بيانات الموظفين
 * - ورقة 2: بيانات متدربات تمهير
 */
class EmployeeWorkbookImport implements WithMultipleSheets, SkipsUnknownSheets
{
    protected EmployeeImport $employeeImport;
    protected TamheerImport $tamheerImport;

    public function __construct()
    {
        $this->employeeImport = new EmployeeImport();
        $this->tamheerImport = new TamheerImport();
    }

    public function sheets(): array
    {
        return [
            0 => $this->employeeImport,
            1 => $this->tamheerImport,
        ];
    }

    public function onUnknownSheet($sheetNumber): void
    {
        // تجاهل الأوراق غير المعروفة
    }

    public function getResults(): array
    {
        return [
            'employees' => $this->employeeImport->getResults(),
            'tamheer'   => $this->tamheerImport->getResults(),
        ];
    }
}
