<?php

namespace App\Imports;

/**
 * استيراد متدربات تمهير
 * يرث من EmployeeImport مع تعديل نوع التوظيف وكلمات الكشف
 */
class TamheerImport extends EmployeeImport
{
    protected array $columnKeywords = [
        'name'             => ['اسم', 'المتدرب', 'الموظف', 'name'],
        'employee_number'  => ['الرقم الوظيفي', 'رقم', 'employee_number'],
        'hire_date'        => ['بداية التدريب', 'بداية', 'تاريخ', 'start'],
        'job_title'        => ['مسمى', 'التدريب', 'الوظيف', 'title'],
        'national_id'      => ['الهوية', 'هوية', 'national_id'],
        'leave_entitled'   => ['المستحق', 'entitled'],
        'leave_used'       => ['المستهلك', 'used'],
        'contract_start'   => ['بداية التدريب', 'بداية'],
        'contract_end'     => ['نهاية التدريب', 'نهاية'],
        'salary'           => ['الراتب', 'الرتب', 'المكافأة', 'salary'],
    ];

    public function __construct()
    {
        parent::__construct('tamheer');
    }
}
