<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class EmployeeImport implements ToCollection
{
    /**
     * خريطة الأعمدة: الحقل => [كلمات مفتاحية للتعرف على العمود]
     */
    protected array $columnKeywords = [
        'name'             => ['اسم', 'الموظف', 'المتدرب', 'name'],
        'employee_number'  => ['الرقم الوظيفي', 'رقم الموظف', 'employee_number', 'emp'],
        'hire_date'        => ['تاريخ الالتحاق', 'تاريخ التعيين', 'الجنسية', 'hire', 'joining', 'بداية التدريب'],
        'job_title'        => ['المسمى', 'الوظيف', 'مسمى', 'job_title', 'position', 'التدريب'],
        'national_id'      => ['الهوية', 'هوية', 'national_id', 'id_number'],
        'leave_entitled'   => ['المستحق', 'مستحق', 'entitled'],
        'leave_used'       => ['مستهلك', 'المستهلك', 'used'],
        'contract_start'   => ['بداية', 'start', 'عقد'],
        'contract_end'     => ['نهاية', 'end'],
        'salary'           => ['الراتب', 'راتب', 'salary', 'الرتب', 'مكافأة'],
    ];

    protected array $columnPositions = [];

    protected array $results = [
        'imported' => 0,
        'updated'  => 0,
        'skipped'  => 0,
        'errors'   => [],
    ];

    protected string $employmentType;

    public function __construct(string $employmentType = 'full_time')
    {
        $this->employmentType = $employmentType;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // الصف الأول هو الترويسة — نستخدمه لتحديد مواقع الأعمدة
        $headerRow = $rows->first()->toArray();
        $this->detectColumnPositions($headerRow);

        DB::beginTransaction();
        try {
            // نبدأ من الصف الثاني (بعد الترويسة)
            foreach ($rows->skip(1) as $index => $row) {
                $this->processRow($row->toArray(), $index + 2);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->results['errors'][] = "خطأ عام: {$e->getMessage()}";
            throw $e;
        }
    }

    /**
     * تحديد مواقع الأعمدة بناءً على ترويسة الملف
     * يمنع تعيين عمود واحد لأكثر من حقل
     */
    protected function detectColumnPositions(array $headerRow): void
    {
        $this->columnPositions = [];
        $assignedColumns = []; // تتبع الأعمدة المعينة لمنع التكرار

        // ترتيب الأولوية: الحقول ذات الكلمات الأكثر تحديداً أولاً
        $priorityOrder = [
            'name', 'employee_number', 'national_id', 'salary',
            'leave_entitled', 'leave_used',
            'contract_start', 'contract_end',
            'hire_date', 'job_title',
        ];

        foreach ($priorityOrder as $field) {
            $keywords = $this->columnKeywords[$field] ?? [];

            foreach ($headerRow as $colIndex => $headerValue) {
                if (empty($headerValue) || in_array($colIndex, $assignedColumns)) {
                    continue;
                }

                $headerStr = mb_strtolower(trim((string) $headerValue));

                foreach ($keywords as $keyword) {
                    $keyword = mb_strtolower($keyword);
                    if (str_contains($headerStr, $keyword)) {
                        $this->columnPositions[$field] = $colIndex;
                        $assignedColumns[] = $colIndex;
                        break 2;
                    }
                }
            }
        }

        // Fallback: الترتيب الافتراضي
        $defaultOrder = ['name', 'employee_number', 'hire_date', 'job_title', 'national_id', 'leave_entitled', 'leave_used', 'contract_start', 'contract_end', 'salary'];

        if (count($this->columnPositions) < 2) {
            foreach ($defaultOrder as $i => $field) {
                if ($i < count($headerRow)) {
                    $this->columnPositions[$field] = $i;
                }
            }
        }
    }

    protected function getColumnValue(array $row, string $field)
    {
        if (!isset($this->columnPositions[$field])) {
            return null;
        }
        return $row[$this->columnPositions[$field]] ?? null;
    }

    protected function processRow(array $row, int $rowNumber): void
    {
        $data = [
            'name'            => $this->getColumnValue($row, 'name'),
            'employee_number' => $this->getColumnValue($row, 'employee_number'),
            'hire_date'       => $this->getColumnValue($row, 'hire_date'),
            'job_title'       => $this->getColumnValue($row, 'job_title'),
            'national_id'     => $this->getColumnValue($row, 'national_id'),
            'leave_entitled'  => $this->getColumnValue($row, 'leave_entitled'),
            'leave_used'      => $this->getColumnValue($row, 'leave_used'),
            'contract_start'  => $this->getColumnValue($row, 'contract_start'),
            'contract_end'    => $this->getColumnValue($row, 'contract_end'),
            'salary'          => $this->getColumnValue($row, 'salary'),
        ];

        // تجاوز الصفوف الفارغة
        if (empty($data['name']) && empty($data['employee_number'])) {
            $this->results['skipped']++;
            return;
        }

        // التحقق من البيانات المطلوبة
        if (empty($data['employee_number'])) {
            $this->results['errors'][] = "صف {$rowNumber}: الرقم الوظيفي مطلوب";
            $this->results['skipped']++;
            return;
        }

        if (empty($data['name'])) {
            $this->results['errors'][] = "صف {$rowNumber}: اسم الموظف مطلوب";
            $this->results['skipped']++;
            return;
        }

        try {
            $nameParts = $this->parseArabicName($data['name']);
            $department = $this->findOrCreateDepartment($data['job_title'] ?? null);
            $position = $this->findOrCreatePosition($data['job_title'] ?? null, $department->id);

            $empNumber = trim((string) $data['employee_number']);

            $employeeData = [
                'department_id'   => $department->id,
                'position_id'     => $position->id,
                'hire_date'       => $this->parseDate($data['hire_date']) ?? now()->toDateString(),
                'employment_type' => $this->employmentType,
                'status'          => 'active',
                'first_name_ar'   => $nameParts['first'],
                'second_name_ar'  => $nameParts['second'],
                'third_name_ar'   => $nameParts['third'],
                'last_name_ar'    => $nameParts['last'],
                'first_name'      => $nameParts['first'],
                'last_name'       => $nameParts['last'],
                'gender'          => $this->guessGender($data['name'], $data['job_title'] ?? ''),
                'national_id'     => $data['national_id'] ?? $this->generateTempId($empNumber),
                'id_type'         => 'national_id',
                'nationality'     => 'Saudi',
                'nationality_ar'  => 'سعودي',
                'email'           => "emp{$empNumber}@medical-erp.com",
                'phone'           => "050000{$empNumber}",
            ];

            // البحث عن الموظف أو إنشاؤه
            $employee = Employee::where('employee_number', $empNumber)->first();

            if ($employee) {
                $employee->update($employeeData);
                $this->results['updated']++;
            } else {
                $employee = Employee::create(array_merge($employeeData, [
                    'id' => Str::uuid(),
                    'employee_number' => $empNumber,
                ]));
                $this->results['imported']++;
            }

            // إنشاء العقد إن وجدت بيانات
            if (!empty($data['contract_start']) || !empty($data['salary'])) {
                $this->createOrUpdateContract($employee, $data);
            }

            // تحديث أرصدة الإجازات
            if (!empty($data['leave_entitled']) || !empty($data['leave_used'])) {
                $this->updateLeaveBalance($employee, $data);
            }

        } catch (\Exception $e) {
            $this->results['errors'][] = "صف {$rowNumber}: {$e->getMessage()}";
            $this->results['skipped']++;
        }
    }

    protected function parseArabicName(?string $fullName): array
    {
        if (empty($fullName)) {
            return ['first' => 'غير محدد', 'second' => null, 'third' => null, 'last' => 'غير محدد'];
        }

        $parts = preg_split('/\s+/', trim($fullName));
        $count = count($parts);

        return [
            'first'  => $parts[0] ?? 'غير محدد',
            'second' => $count >= 3 ? $parts[1] : null,
            'third'  => $count >= 4 ? $parts[2] : null,
            'last'   => $count >= 2 ? $parts[$count - 1] : ($parts[0] ?? 'غير محدد'),
        ];
    }

    protected function guessGender(string $name, string $jobTitle): string
    {
        $femaleJobIndicators = ['طبيبة', 'ممرضة', 'أخصائية', 'منسقة', 'أمينة', 'متدربة', 'محاسبة', 'مديرة'];
        foreach ($femaleJobIndicators as $indicator) {
            if (str_contains($jobTitle, $indicator)) {
                return 'female';
            }
        }

        $femaleNames = ['نورة', 'شيرين', 'وفاء', 'منار', 'منيرة', 'ماريا', 'سارة', 'فاطمة', 'عائشة', 'مريم', 'هند', 'دلال', 'أمل', 'ريم', 'لمى'];
        $firstName = explode(' ', $name)[0] ?? '';
        if (in_array($firstName, $femaleNames)) {
            return 'female';
        }

        $femaleNameEndings = ['ة', 'اء', 'ى'];
        foreach ($femaleNameEndings as $ending) {
            if (str_ends_with($firstName, $ending)) {
                return 'female';
            }
        }

        return 'male';
    }

    protected function findOrCreateDepartment(?string $jobTitle): Department
    {
        $departmentMapping = [
            'طبيب'    => ['code' => 'MED',   'name' => 'Medical Department',  'name_ar' => 'القسم الطبي'],
            'طبيبة'   => ['code' => 'MED',   'name' => 'Medical Department',  'name_ar' => 'القسم الطبي'],
            'أسنان'   => ['code' => 'DENT',  'name' => 'Dental Department',   'name_ar' => 'قسم الأسنان'],
            'ممرض'    => ['code' => 'NURS',  'name' => 'Nursing Department',  'name_ar' => 'قسم التمريض'],
            'ممرضة'   => ['code' => 'NURS',  'name' => 'Nursing Department',  'name_ar' => 'قسم التمريض'],
            'صيدل'    => ['code' => 'PHARM', 'name' => 'Pharmacy Department', 'name_ar' => 'قسم الصيدلة'],
            'مختبر'   => ['code' => 'LAB',   'name' => 'Laboratory',          'name_ar' => 'قسم المختبر'],
            'أشعة'    => ['code' => 'RAD',   'name' => 'Radiology',           'name_ar' => 'قسم الأشعة'],
            'إدار'    => ['code' => 'ADMIN', 'name' => 'Administration',      'name_ar' => 'الإدارة العامة'],
            'محاسب'   => ['code' => 'FIN',   'name' => 'Finance',             'name_ar' => 'المالية'],
            'استقبال' => ['code' => 'RECEP', 'name' => 'Reception',           'name_ar' => 'الاستقبال'],
            'تمريض'   => ['code' => 'NURS',  'name' => 'Nursing Department',  'name_ar' => 'قسم التمريض'],
            'مستودع'  => ['code' => 'STORE', 'name' => 'Warehouse',           'name_ar' => 'المستودعات'],
            'عيادة'   => ['code' => 'CLIN',  'name' => 'Clinic Operations',   'name_ar' => 'العيادات'],
        ];

        if ($jobTitle) {
            foreach ($departmentMapping as $keyword => $dept) {
                if (str_contains($jobTitle, $keyword)) {
                    return Department::firstOrCreate(
                        ['code' => $dept['code']],
                        array_merge($dept, ['id' => Str::uuid(), 'is_active' => true, 'sort_order' => 0])
                    );
                }
            }
        }

        return Department::firstOrCreate(
            ['code' => 'GEN'],
            ['id' => Str::uuid(), 'name' => 'General', 'name_ar' => 'عام', 'is_active' => true, 'sort_order' => 99]
        );
    }

    protected function findOrCreatePosition(?string $jobTitle, string $departmentId): Position
    {
        if (empty($jobTitle)) {
            $jobTitle = 'موظف';
        }

        $jobTitle = trim($jobTitle);
        $code = 'POS-' . mb_substr(md5($jobTitle), 0, 6);

        return Position::firstOrCreate(
            ['code' => $code],
            [
                'id'            => Str::uuid(),
                'title'         => $jobTitle,
                'title_ar'      => $jobTitle,
                'department_id' => $departmentId,
                'category'      => $this->guessPositionCategory($jobTitle),
                'is_active'     => true,
                'sort_order'    => 0,
            ]
        );
    }

    protected function guessPositionCategory(string $jobTitle): string
    {
        $medicalKeywords = ['طبيب', 'طبيبة', 'ممرض', 'ممرضة', 'صيدل', 'أخصائي', 'أخصائية', 'فني', 'مختبر', 'أشعة'];
        foreach ($medicalKeywords as $keyword) {
            if (str_contains($jobTitle, $keyword)) {
                return 'medical';
            }
        }

        $adminKeywords = ['إدار', 'مدير', 'محاسب', 'سكرتير', 'استقبال', 'منسق'];
        foreach ($adminKeywords as $keyword) {
            if (str_contains($jobTitle, $keyword)) {
                return 'administrative';
            }
        }

        return 'support';
    }

    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        $value = trim((string) $value);

        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (preg_match('#^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})$#', $value, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return null;
    }

    protected function generateTempId(string $empNumber): string
    {
        return 'TEMP' . str_pad($empNumber, 6, '0', STR_PAD_LEFT);
    }

    protected function createOrUpdateContract(Employee $employee, array $data): void
    {
        $startDate = $this->parseDate($data['contract_start']);
        $endDate = $this->parseDate($data['contract_end']);
        $salary = is_numeric($data['salary'] ?? null) ? (float) $data['salary'] : 0;

        if (!$startDate) {
            return;
        }

        $systemUser = User::first();
        if (!$systemUser) {
            return;
        }

        $contractNumber = 'CON-' . $employee->employee_number . '-' . date('Y');

        DB::table('contracts')->updateOrInsert(
            ['contract_number' => $contractNumber],
            [
                'id'                 => Str::uuid(),
                'employee_id'        => $employee->id,
                'contract_type'      => $this->employmentType === 'tamheer' ? 'tamheer' : 'full_time',
                'status'             => 'active',
                'start_date'         => $startDate,
                'end_date'           => $endDate,
                'duration_months'    => $startDate && $endDate
                    ? (int) \Carbon\Carbon::parse($startDate)->diffInMonths(\Carbon\Carbon::parse($endDate))
                    : null,
                'basic_salary'       => $salary,
                'total_salary'       => $salary,
                'annual_leave_days'  => 30,
                'sick_leave_days'    => 30,
                'notice_period_days' => 60,
                'created_by'         => $systemUser->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]
        );
    }

    protected function updateLeaveBalance(Employee $employee, array $data): void
    {
        $entitled = is_numeric($data['leave_entitled'] ?? null) ? (float) $data['leave_entitled'] : null;
        $used = is_numeric($data['leave_used'] ?? null) ? (float) $data['leave_used'] : null;

        if ($entitled === null && $used === null) {
            return;
        }

        $annualLeaveType = DB::table('leave_types')->where('code', 'ANNUAL')->first();
        if (!$annualLeaveType) {
            $leaveTypeId = Str::uuid();
            DB::table('leave_types')->insert([
                'id'                     => $leaveTypeId,
                'code'                   => 'ANNUAL',
                'name'                   => 'Annual Leave',
                'name_ar'                => 'إجازة سنوية',
                'category'               => 'annual',
                'default_days_per_year'  => 30,
                'is_paid'                => true,
                'pay_percentage'         => 100,
                'requires_attachment'    => false,
                'is_active'              => true,
                'carries_forward'        => true,
                'max_carry_forward_days' => 15,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        } else {
            $leaveTypeId = $annualLeaveType->id;
        }

        $year = date('Y');

        DB::table('leave_balances')->updateOrInsert(
            [
                'employee_id'   => $employee->id,
                'leave_type_id' => $leaveTypeId,
                'year'          => $year,
            ],
            [
                'id'                 => Str::uuid(),
                'total_entitled'     => $entitled ?? 30,
                'used'               => $used ?? 0,
                'remaining'          => ($entitled ?? 30) - ($used ?? 0),
                'carried_forward'    => 0,
                'additional_granted' => 0,
                'pending'            => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]
        );
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
