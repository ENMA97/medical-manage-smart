<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\CustodyItem;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LetterTemplate;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * بيانات تجريبية للعرض والاختبار
 * يتطلب تنفيذ FoundationSeeder أولاً
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->warn('⚠️  يجب تنفيذ FoundationSeeder أولاً');
            return;
        }

        // ─── 1. أنواع الإجازات ───
        $this->command->info('📅 إنشاء أنواع الإجازات...');
        $leaveTypes = $this->seedLeaveTypes();

        // ─── 2. العقود ───
        $this->command->info('📄 إنشاء العقود...');
        $this->seedContracts($employees);

        // ─── 3. أرصدة الإجازات ───
        $this->command->info('📊 إنشاء أرصدة الإجازات...');
        $this->seedLeaveBalances($employees, $leaveTypes);

        // ─── 4. طلبات الإجازات ───
        $this->command->info('🏖️  إنشاء طلبات الإجازات...');
        $this->seedLeaveRequests($employees, $leaveTypes);

        // ─── 5. قوالب الخطابات ───
        $this->command->info('📝 إنشاء قوالب الخطابات...');
        $this->seedLetterTemplates();

        // ─── 6. العهد ───
        $this->command->info('📦 إنشاء بيانات العهد...');
        $this->seedCustody($employees);

        // ─── 7. إعدادات النظام ───
        $this->command->info('⚙️  إنشاء إعدادات النظام...');
        $this->seedSettings();

        // ─── 8. الإشعارات ───
        $this->command->info('🔔 إنشاء إشعارات تجريبية...');
        $this->seedNotifications();

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║    ✅ Demo data seeded successfully!         ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
    }

    private function seedLeaveTypes(): array
    {
        $types = [
            ['code' => 'ANNUAL', 'name' => 'Annual Leave', 'name_ar' => 'إجازة سنوية', 'category' => 'annual', 'default_days_per_year' => 21, 'is_paid' => true, 'pay_percentage' => 100],
            ['code' => 'SICK', 'name' => 'Sick Leave', 'name_ar' => 'إجازة مرضية', 'category' => 'sick', 'default_days_per_year' => 30, 'is_paid' => true, 'pay_percentage' => 100, 'requires_attachment' => true],
            ['code' => 'UNPAID', 'name' => 'Unpaid Leave', 'name_ar' => 'إجازة بدون راتب', 'category' => 'unpaid', 'default_days_per_year' => 15, 'is_paid' => false, 'pay_percentage' => 0],
            ['code' => 'MATERNITY', 'name' => 'Maternity Leave', 'name_ar' => 'إجازة أمومة', 'category' => 'maternity', 'default_days_per_year' => 70, 'is_paid' => true, 'pay_percentage' => 100],
            ['code' => 'MARRIAGE', 'name' => 'Marriage Leave', 'name_ar' => 'إجازة زواج', 'category' => 'special', 'default_days_per_year' => 5, 'is_paid' => true, 'pay_percentage' => 100],
            ['code' => 'BEREAVEMENT', 'name' => 'Bereavement Leave', 'name_ar' => 'إجازة وفاة', 'category' => 'special', 'default_days_per_year' => 5, 'is_paid' => true, 'pay_percentage' => 100],
        ];

        $created = [];
        foreach ($types as $i => $type) {
            $created[] = LeaveType::firstOrCreate(
                ['code' => $type['code']],
                array_merge($type, [
                    'id' => Str::uuid(),
                    'max_days_per_request' => $type['default_days_per_year'],
                    'min_days_per_request' => 1,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ])
            );
        }

        return $created;
    }

    private function seedContracts($employees): void
    {
        foreach ($employees as $i => $employee) {
            Contract::firstOrCreate(
                ['employee_id' => $employee->id, 'status' => 'active'],
                [
                    'id' => Str::uuid(),
                    'contract_number' => 'CNT-' . date('Y') . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                    'contract_type' => 'full_time',
                    'status' => 'active',
                    'start_date' => $employee->hire_date ?? '2024-01-01',
                    'end_date' => now()->addYear()->format('Y-m-d'),
                    'duration_months' => 12,
                    'basic_salary' => 5000 + ($i * 2000),
                    'housing_allowance' => 1250 + ($i * 500),
                    'transport_allowance' => 500,
                    'food_allowance' => 300,
                    'phone_allowance' => 150,
                    'other_allowances' => 0,
                    'total_salary' => 7200 + ($i * 2500),
                    'annual_leave_days' => 21,
                    'sick_leave_days' => 30,
                    'notice_period_days' => 30,
                    'created_by' => User::where('user_type', 'super_admin')->first()?->id,
                ]
            );
        }
    }

    private function seedLeaveBalances($employees, $leaveTypes): void
    {
        $year = date('Y');
        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                    [
                        'id' => Str::uuid(),
                        'total_days' => $type->default_days_per_year,
                        'used_days' => rand(0, min(5, $type->default_days_per_year)),
                        'pending_days' => 0,
                        'remaining_days' => $type->default_days_per_year - rand(0, min(5, $type->default_days_per_year)),
                        'carried_forward_days' => 0,
                    ]
                );
            }
        }
    }

    private function seedLeaveRequests($employees, $leaveTypes): void
    {
        if (empty($leaveTypes)) return;
        $annual = $leaveTypes[0]; // ANNUAL

        // Create some sample leave requests
        $statuses = ['pending', 'approved', 'rejected'];
        foreach ($employees->take(3) as $i => $employee) {
            LeaveRequest::create([
                'id' => Str::uuid(),
                'request_number' => 'LR-' . date('Y') . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'employee_id' => $employee->id,
                'leave_type_id' => $annual->id,
                'start_date' => now()->addDays(10 + ($i * 15))->format('Y-m-d'),
                'end_date' => now()->addDays(14 + ($i * 15))->format('Y-m-d'),
                'total_days' => 5,
                'reason' => 'إجازة عائلية',
                'status' => $statuses[$i % 3],
            ]);
        }
    }

    private function seedLetterTemplates(): void
    {
        $templates = [
            [
                'name' => 'Salary Certificate',
                'name_ar' => 'شهادة راتب',
                'letter_type' => 'salary_certificate',
                'body_template' => "To Whom It May Concern,\n\nThis is to certify that {employee_name} (Employee #{employee_number}) is employed at our organization in the {department} department as {position} since {hire_date}.\n\nDate: {date}",
                'body_template_ar' => "إلى من يهمه الأمر،\n\nنشهد بأن السيد/ة {employee_name} يعمل لدى مؤسستنا في قسم {department} بمسمى {position} وذلك اعتباراً من {hire_date}.\n\nالتاريخ: {date}",
                'requires_approval' => true,
            ],
            [
                'name' => 'Employment Certificate',
                'name_ar' => 'شهادة عمل',
                'letter_type' => 'employment_certificate',
                'body_template' => "To Whom It May Concern,\n\nWe confirm that {employee_name} is currently employed in our organization.\n\nDate: {date}",
                'body_template_ar' => "إلى من يهمه الأمر،\n\nنؤكد بأن {employee_name} يعمل حالياً لدى مؤسستنا.\n\nالتاريخ: {date}",
                'requires_approval' => false,
            ],
            [
                'name' => 'Experience Certificate',
                'name_ar' => 'شهادة خبرة',
                'letter_type' => 'experience_certificate',
                'body_template' => "To Whom It May Concern,\n\nThis certifies that {employee_name} has worked at our organization from {hire_date} as {position}.\n\nDate: {date}",
                'body_template_ar' => "إلى من يهمه الأمر،\n\nنشهد بأن {employee_name} عمل لدى مؤسستنا بداية من {hire_date} بمسمى {position}.\n\nالتاريخ: {date}",
                'requires_approval' => true,
            ],
        ];

        foreach ($templates as $i => $t) {
            LetterTemplate::firstOrCreate(
                ['letter_type' => $t['letter_type']],
                array_merge($t, [
                    'id' => Str::uuid(),
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ])
            );
        }
    }

    private function seedCustody($employees): void
    {
        $items = [
            ['item_name' => 'Laptop', 'item_name_ar' => 'حاسب محمول', 'item_type' => 'equipment', 'serial_number' => 'LP-001'],
            ['item_name' => 'Mobile Phone', 'item_name_ar' => 'هاتف جوال', 'item_type' => 'equipment', 'serial_number' => 'PH-001'],
            ['item_name' => 'Office Key', 'item_name_ar' => 'مفتاح مكتب', 'item_type' => 'key', 'serial_number' => 'KEY-001'],
            ['item_name' => 'Parking Card', 'item_name_ar' => 'بطاقة مواقف', 'item_type' => 'card', 'serial_number' => 'PKG-001'],
        ];

        foreach ($employees->take(3) as $i => $employee) {
            if (isset($items[$i])) {
                CustodyItem::firstOrCreate(
                    ['serial_number' => $items[$i]['serial_number']],
                    array_merge($items[$i], [
                        'id' => Str::uuid(),
                        'employee_id' => $employee->id,
                        'status' => 'active',
                        'assigned_date' => $employee->hire_date ?? '2024-01-01',
                        'notes' => 'تم التسليم بحالة جيدة',
                    ])
                );
            }
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'مستشفى النخبة الطبي', 'group' => 'general'],
            ['key' => 'company_name_en', 'value' => 'Elite Medical Hospital', 'group' => 'general'],
            ['key' => 'company_phone', 'value' => '920001234', 'group' => 'general'],
            ['key' => 'company_email', 'value' => 'info@elite-medical.sa', 'group' => 'general'],
            ['key' => 'company_address', 'value' => 'الرياض، حي العليا', 'group' => 'general'],
            ['key' => 'gosi_employee_rate', 'value' => '9.75', 'group' => 'payroll'],
            ['key' => 'gosi_employer_rate', 'value' => '11.75', 'group' => 'payroll'],
            ['key' => 'annual_leave_default_days', 'value' => '21', 'group' => 'leave'],
            ['key' => 'sick_leave_default_days', 'value' => '30', 'group' => 'leave'],
            ['key' => 'probation_period_days', 'value' => '90', 'group' => 'contracts'],
        ];

        foreach ($settings as $s) {
            SystemSetting::firstOrCreate(
                ['key' => $s['key']],
                array_merge($s, ['id' => Str::uuid()])
            );
        }
    }

    private function seedNotifications(): void
    {
        $users = User::all();
        $messages = [
            ['title' => 'مرحباً بك في النظام', 'message' => 'تم تفعيل حسابك بنجاح. يمكنك الآن الوصول لجميع الخدمات.', 'type' => 'system'],
            ['title' => 'تذكير: تجديد العقود', 'message' => 'يوجد عقود تنتهي خلال 30 يوماً. يرجى مراجعتها.', 'type' => 'contract'],
            ['title' => 'طلب إجازة جديد', 'message' => 'تم تقديم طلب إجازة جديد بانتظار الموافقة.', 'type' => 'leave'],
        ];

        foreach ($users as $user) {
            foreach ($messages as $i => $msg) {
                Notification::firstOrCreate(
                    ['user_id' => $user->id, 'title' => $msg['title']],
                    array_merge($msg, [
                        'id' => Str::uuid(),
                        'user_id' => $user->id,
                        'read_at' => $i === 0 ? now() : null,
                    ])
                );
            }
        }
    }
}
