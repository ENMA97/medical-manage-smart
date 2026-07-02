<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * بيانات الموظفين الفعلية من جدول البيانات
 * يتم تشغيله بعد FoundationSeeder
 *
 * php artisan db:seed --class=RealEmployeeSeeder
 */
class RealEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ─── أقسام إضافية ───
        $dentDept = Department::firstOrCreate(
            ['code' => 'DENT'],
            ['id' => Str::uuid(), 'name' => 'Dental Department', 'name_ar' => 'قسم الأسنان', 'is_active' => true, 'sort_order' => 7]
        );

        $pharmacyDept = Department::firstOrCreate(
            ['code' => 'PHARM'],
            ['id' => Str::uuid(), 'name' => 'Pharmacy Department', 'name_ar' => 'قسم الصيدلة', 'is_active' => true, 'sort_order' => 8]
        );

        $labDept = Department::firstOrCreate(
            ['code' => 'LAB'],
            ['id' => Str::uuid(), 'name' => 'Laboratory', 'name_ar' => 'قسم المختبر', 'is_active' => true, 'sort_order' => 9]
        );

        $recepDept = Department::firstOrCreate(
            ['code' => 'RECEP'],
            ['id' => Str::uuid(), 'name' => 'Reception', 'name_ar' => 'الاستقبال', 'is_active' => true, 'sort_order' => 10]
        );

        $storeDept = Department::firstOrCreate(
            ['code' => 'STORE'],
            ['id' => Str::uuid(), 'name' => 'Warehouse', 'name_ar' => 'المستودعات', 'is_active' => true, 'sort_order' => 11]
        );

        $clinicDept = Department::firstOrCreate(
            ['code' => 'CLIN'],
            ['id' => Str::uuid(), 'name' => 'Clinic Operations', 'name_ar' => 'العيادات', 'is_active' => true, 'sort_order' => 12]
        );

        // الأقسام الموجودة
        $medDept = Department::where('code', 'MED')->first();
        $nursDept = Department::where('code', 'NURS')->first();
        $adminDept = Department::where('code', 'ADMIN')->first();

        // ─── مسميات وظيفية إضافية ───
        $positions = [];

        $positionDefs = [
            ['code' => 'CONS_PED', 'title' => 'Consultant Pediatrician', 'title_ar' => 'طبيب إستشاري أطفال', 'dept' => $medDept, 'cat' => 'medical'],
            ['code' => 'DENTIST', 'title' => 'Dentist', 'title_ar' => 'طبيب أسنان', 'dept' => $dentDept, 'cat' => 'medical'],
            ['code' => 'GP', 'title' => 'General Practitioner', 'title_ar' => 'طبيب عام', 'dept' => $medDept, 'cat' => 'medical'],
            ['code' => 'DERM', 'title' => 'Dermatologist', 'title_ar' => 'طبيب جلدية', 'dept' => $medDept, 'cat' => 'medical'],
            ['code' => 'SPEC', 'title' => 'Specialist Doctor', 'title_ar' => 'طبيب اختصاصي', 'dept' => $medDept, 'cat' => 'medical'],
            ['code' => 'GP_F', 'title' => 'General Practitioner (F)', 'title_ar' => 'طبيبة عامة', 'dept' => $medDept, 'cat' => 'medical'],
            ['code' => 'PHARM', 'title' => 'Pharmacist', 'title_ar' => 'صيدلي', 'dept' => $pharmacyDept, 'cat' => 'medical'],
            ['code' => 'LAB_TECH', 'title' => 'Lab Technician', 'title_ar' => 'فني مختبر', 'dept' => $labDept, 'cat' => 'medical'],
            ['code' => 'NURSE_F', 'title' => 'Nurse (F)', 'title_ar' => 'ممرضة', 'dept' => $nursDept, 'cat' => 'medical'],
            ['code' => 'RECEP_F', 'title' => 'Receptionist', 'title_ar' => 'موظفة استقبال', 'dept' => $recepDept, 'cat' => 'administrative'],
            ['code' => 'STORE_K', 'title' => 'Warehouse Keeper', 'title_ar' => 'أمينة مستودع', 'dept' => $storeDept, 'cat' => 'administrative'],
            ['code' => 'CLIN_COORD', 'title' => 'Clinic Coordinator', 'title_ar' => 'منسقة عيادة', 'dept' => $clinicDept, 'cat' => 'administrative'],
            ['code' => 'DENT_PROS', 'title' => 'Prosthodontist', 'title_ar' => 'طبيب أسنان تركيبات', 'dept' => $dentDept, 'cat' => 'medical'],
        ];

        foreach ($positionDefs as $def) {
            $positions[$def['code']] = Position::firstOrCreate(
                ['code' => $def['code']],
                [
                    'id' => Str::uuid(),
                    'title' => $def['title'],
                    'title_ar' => $def['title_ar'],
                    'department_id' => $def['dept']->id,
                    'category' => $def['cat'],
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );
        }

        // ─── بيانات الموظفين من الجدول ───
        $employees = [
            // تقرير بيانات الموظفين
            [
                'employee_number' => '53',
                'first_name_ar' => 'خالد', 'second_name_ar' => 'عبدالله', 'third_name_ar' => 'سعيد', 'last_name_ar' => 'أحمد',
                'first_name' => 'Khalid', 'last_name' => 'Ahmed',
                'hire_date' => '2015-07-04',
                'position_code' => 'CONS_PED',
                'national_id' => '2392405607',
                'gender' => 'male',
                'contract_start' => '2024-07-04',
                'contract_end' => '2025-07-03',
            ],
            [
                'employee_number' => '307',
                'first_name_ar' => 'شيرين', 'second_name_ar' => 'منصور', 'third_name_ar' => 'محمد', 'last_name_ar' => 'يحيى',
                'first_name' => 'Shereen', 'last_name' => 'Yahya',
                'hire_date' => '2024-03-17',
                'position_code' => 'DENTIST',
                'national_id' => '2574131616',
                'gender' => 'female',
                'contract_start' => '2024-03-18',
                'contract_end' => '2026-03-17',
            ],
            [
                'employee_number' => '18',
                'first_name_ar' => 'محمد', 'second_name_ar' => 'علي', 'last_name_ar' => 'الشهري',
                'first_name' => 'Mohammed', 'last_name' => 'Al-Shahri',
                'hire_date' => '2009-04-11',
                'position_code' => 'GP',
                'national_id' => '2271768220',
                'gender' => 'male',
                'leave_entitled' => 46.83,
                'leave_used' => 74,
                'contract_start' => '2024-05-25',
                'contract_end' => '2026-05-14',
            ],
            [
                'employee_number' => '302',
                'first_name_ar' => 'محمد', 'second_name_ar' => 'أحمد', 'third_name_ar' => 'محمد', 'last_name_ar' => 'حسن',
                'first_name' => 'Mohammed', 'last_name' => 'Hassan',
                'hire_date' => '2024-12-09',
                'position_code' => 'GP',
                'national_id' => '2591782053',
                'gender' => 'male',
                'leave_entitled' => 13.61,
                'leave_used' => 12,
            ],
            [
                'employee_number' => '30',
                'first_name_ar' => 'سعود', 'second_name_ar' => 'محمد', 'last_name_ar' => 'مغلوث',
                'first_name' => 'Saud', 'last_name' => 'Mughlouth',
                'hire_date' => '2015-03-12',
                'position_code' => 'GP',
                'national_id' => '2390325138',
                'gender' => 'male',
                'leave_entitled' => 35.58,
                'leave_used' => 60,
                'contract_start' => '2024-06-01',
                'contract_end' => '2025-05-31',
            ],
            [
                'employee_number' => '245',
                'first_name_ar' => 'ياسر', 'second_name_ar' => 'فيصل', 'last_name_ar' => 'السوسي',
                'first_name' => 'Yasser', 'last_name' => 'Al-Sousi',
                'hire_date' => '2023-01-18',
                'position_code' => 'SPEC',
                'national_id' => '2542448226',
                'gender' => 'male',
                'leave_entitled' => 37.92,
                'leave_used' => 28,
                'contract_start' => '2025-01-18',
                'contract_end' => '2027-01-17',
            ],
            [
                'employee_number' => '289',
                'first_name_ar' => 'أحمد', 'last_name_ar' => 'الغامدي',
                'first_name' => 'Ahmed', 'last_name' => 'Al-Ghamdi',
                'hire_date' => '2025-05-18',
                'position_code' => 'SPEC',
                'national_id' => '2580790026',
                'gender' => 'male',
                'leave_entitled' => 23.57,
                'leave_used' => 14,
                'contract_start' => '2024-05-18',
                'contract_end' => '2026-05-17',
            ],
            [
                'employee_number' => '233',
                'first_name_ar' => 'وفاء', 'last_name_ar' => 'العمري',
                'first_name' => 'Wafaa', 'last_name' => 'Al-Omari',
                'hire_date' => '2022-06-12',
                'position_code' => 'GP_F',
                'national_id' => '2522577671',
                'gender' => 'female',
                'leave_entitled' => 25.5,
                'leave_used' => 87,
                'contract_start' => '2024-06-12',
                'contract_end' => '2026-06-11',
            ],
            [
                'employee_number' => '310',
                'first_name_ar' => 'عبدالرحمن', 'last_name_ar' => 'الزهراني',
                'first_name' => 'Abdulrahman', 'last_name' => 'Al-Zahrani',
                'hire_date' => '2025-05-03',
                'position_code' => 'DENT_PROS',
                'national_id' => '2606223960',
                'gender' => 'male',
                'leave_entitled' => 17.15,
                'leave_used' => 0,
                'contract_start' => '2025-05-03',
                'contract_end' => '2027-05-02',
            ],
            [
                'employee_number' => '242',
                'first_name_ar' => 'داؤود', 'second_name_ar' => 'محمد', 'last_name_ar' => 'عمر',
                'first_name' => 'Daoud', 'last_name' => 'Omar',
                'hire_date' => '2022-07-27',
                'position_code' => 'SPEC',
                'national_id' => '2527573337',
                'gender' => 'male',
                'leave_entitled' => 16.13,
                'leave_used' => 60,
                'contract_start' => '2024-07-27',
                'contract_end' => '2026-07-26',
            ],
        ];

        // ─── بيانات متدربات تمهير ───
        $tamheerEmployees = [
            [
                'employee_number' => 'T001',
                'first_name_ar' => 'منار', 'second_name_ar' => 'أحمد', 'third_name_ar' => 'أحمد', 'last_name_ar' => 'سحلقي',
                'first_name' => 'Manar', 'last_name' => 'Sahlqi',
                'hire_date' => '2025-10-20',
                'position_code' => 'STORE_K',
                'national_id' => '1106640467',
                'gender' => 'female',
                'employment_type' => 'tamheer',
                'contract_start' => '2025-10-20',
                'contract_end' => '2026-04-20',
            ],
            [
                'employee_number' => '325',
                'first_name_ar' => 'منيرة', 'second_name_ar' => 'أحمد', 'last_name_ar' => 'خواجي',
                'first_name' => 'Muneerah', 'last_name' => 'Khawaji',
                'hire_date' => '2025-12-01',
                'position_code' => 'CLIN_COORD',
                'national_id' => '1112478621',
                'gender' => 'female',
                'employment_type' => 'tamheer',
                'contract_start' => '2025-12-01',
                'contract_end' => '2026-06-01',
            ],
        ];

        $allEmployees = array_merge($employees, $tamheerEmployees);
        $created = 0;
        $skipped = 0;

        foreach ($allEmployees as $emp) {
            // تجاوز إذا الرقم الوظيفي موجود
            if (Employee::where('employee_number', $emp['employee_number'])->exists()) {
                $skipped++;
                continue;
            }

            $position = $positions[$emp['position_code']] ?? Position::where('code', 'DOC')->first();
            $department = $position->department ?? $medDept;

            Employee::create([
                'id' => Str::uuid(),
                'employee_number' => $emp['employee_number'],
                'department_id' => $department->id,
                'position_id' => $position->id,
                'hire_date' => $emp['hire_date'],
                'actual_start_date' => $emp['hire_date'],
                'employment_type' => $emp['employment_type'] ?? 'full_time',
                'status' => 'active',
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'first_name_ar' => $emp['first_name_ar'],
                'second_name_ar' => $emp['second_name_ar'] ?? null,
                'third_name_ar' => $emp['third_name_ar'] ?? null,
                'last_name_ar' => $emp['last_name_ar'],
                'gender' => $emp['gender'],
                'national_id' => $emp['national_id'],
                'id_type' => 'national_id',
                'nationality' => 'Saudi',
                'nationality_ar' => $emp['gender'] === 'female' ? 'سعودية' : 'سعودي',
                'email' => "emp{$emp['employee_number']}@medical-erp.com",
                'phone' => "050000{$emp['employee_number']}",
            ]);

            $created++;
        }

        $this->command->info('');
        $this->command->info("╔══════════════════════════════════════════════════════╗");
        $this->command->info("║  ✅ Real employee data seeded!                       ║");
        $this->command->info("║  Created: {$created} | Skipped: {$skipped}                          ║");
        $this->command->info("╚══════════════════════════════════════════════════════╝");
        $this->command->info('');
        $this->command->info('💡 For complete data import, use the Excel import feature:');
        $this->command->info('   POST /api/import/employees (with Excel file)');
    }
}
