<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * بيانات أساسية للنظام
 * يُنشئ: أقسام + مسميات + موظف مدير + حساب مستخدم
 */
class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. الأقسام ───
        $hrDept = Department::firstOrCreate(
            ['code' => 'HR'],
            ['id' => Str::uuid(), 'name' => 'Human Resources', 'name_ar' => 'الموارد البشرية', 'is_active' => true, 'sort_order' => 1]
        );

        $adminDept = Department::firstOrCreate(
            ['code' => 'ADMIN'],
            ['id' => Str::uuid(), 'name' => 'Administration', 'name_ar' => 'الإدارة العامة', 'is_active' => true, 'sort_order' => 2]
        );

        $medicalDept = Department::firstOrCreate(
            ['code' => 'MED'],
            ['id' => Str::uuid(), 'name' => 'Medical Department', 'name_ar' => 'القسم الطبي', 'is_active' => true, 'sort_order' => 3]
        );

        $nursingDept = Department::firstOrCreate(
            ['code' => 'NURS'],
            ['id' => Str::uuid(), 'name' => 'Nursing Department', 'name_ar' => 'قسم التمريض', 'is_active' => true, 'sort_order' => 4]
        );

        $financeDept = Department::firstOrCreate(
            ['code' => 'FIN'],
            ['id' => Str::uuid(), 'name' => 'Finance', 'name_ar' => 'المالية', 'is_active' => true, 'sort_order' => 5]
        );

        $itDept = Department::firstOrCreate(
            ['code' => 'IT'],
            ['id' => Str::uuid(), 'name' => 'Information Technology', 'name_ar' => 'تقنية المعلومات', 'is_active' => true, 'sort_order' => 6]
        );

        // ─── 2. المسميات الوظيفية ───
        $gmPosition = Position::firstOrCreate(
            ['code' => 'GM'],
            ['id' => Str::uuid(), 'title' => 'General Manager', 'title_ar' => 'المدير العام', 'department_id' => $adminDept->id, 'category' => 'administrative', 'is_active' => true, 'sort_order' => 1]
        );

        $hrManagerPosition = Position::firstOrCreate(
            ['code' => 'HRM'],
            ['id' => Str::uuid(), 'title' => 'HR Manager', 'title_ar' => 'مدير الموارد البشرية', 'department_id' => $hrDept->id, 'category' => 'administrative', 'is_active' => true, 'sort_order' => 2]
        );

        $doctorPosition = Position::firstOrCreate(
            ['code' => 'DOC'],
            ['id' => Str::uuid(), 'title' => 'Doctor', 'title_ar' => 'طبيب', 'department_id' => $medicalDept->id, 'category' => 'medical', 'is_active' => true, 'sort_order' => 3]
        );

        $nursePosition = Position::firstOrCreate(
            ['code' => 'NRS'],
            ['id' => Str::uuid(), 'title' => 'Nurse', 'title_ar' => 'ممرض/ة', 'department_id' => $nursingDept->id, 'category' => 'medical', 'is_active' => true, 'sort_order' => 4]
        );

        Position::firstOrCreate(
            ['code' => 'ACC'],
            ['id' => Str::uuid(), 'title' => 'Accountant', 'title_ar' => 'محاسب', 'department_id' => $financeDept->id, 'category' => 'administrative', 'is_active' => true, 'sort_order' => 5]
        );

        Position::firstOrCreate(
            ['code' => 'DEV'],
            ['id' => Str::uuid(), 'title' => 'Software Developer', 'title_ar' => 'مطور برمجيات', 'department_id' => $itDept->id, 'category' => 'technical', 'is_active' => true, 'sort_order' => 6]
        );

        Position::firstOrCreate(
            ['code' => 'ADMN'],
            ['id' => Str::uuid(), 'title' => 'Admin Manager', 'title_ar' => 'المدير الإداري', 'department_id' => $adminDept->id, 'category' => 'administrative', 'is_active' => true, 'sort_order' => 7]
        );

        // ─── 3. الموظفون ───

        // المدير العام (Super Admin)
        $gmEmployee = Employee::firstOrCreate(
            ['employee_number' => '1001'],
            [
                'id' => Str::uuid(), 'department_id' => $adminDept->id, 'position_id' => $gmPosition->id,
                'hire_date' => '2020-01-01', 'actual_start_date' => '2020-01-01', 'employment_type' => 'full_time', 'status' => 'active',
                'first_name' => 'Abdullah', 'last_name' => 'Al-Rashid', 'first_name_ar' => 'عبدالله', 'last_name_ar' => 'الراشد',
                'gender' => 'male', 'date_of_birth' => '1980-05-15', 'national_id' => '1099887766', 'id_type' => 'national_id',
                'nationality' => 'Saudi', 'nationality_ar' => 'سعودي', 'email' => 'gm@medical-erp.com', 'phone' => '0512345001',
            ]
        );

        // مدير الموارد البشرية
        $hrEmployee = Employee::firstOrCreate(
            ['employee_number' => '1002'],
            [
                'id' => Str::uuid(), 'department_id' => $hrDept->id, 'position_id' => $hrManagerPosition->id,
                'direct_manager_id' => $gmEmployee->id, 'hire_date' => '2021-03-01', 'actual_start_date' => '2021-03-01',
                'employment_type' => 'full_time', 'status' => 'active',
                'first_name' => 'Nora', 'last_name' => 'Al-Fahd', 'first_name_ar' => 'نورة', 'last_name_ar' => 'الفهد',
                'gender' => 'female', 'date_of_birth' => '1990-08-20', 'national_id' => '1088776655', 'id_type' => 'national_id',
                'nationality' => 'Saudi', 'nationality_ar' => 'سعودية', 'email' => 'hr@medical-erp.com', 'phone' => '0512345002',
            ]
        );

        // طبيب (لاختبار مسار الإجازات)
        $doctorEmployee = Employee::firstOrCreate(
            ['employee_number' => '2001'],
            [
                'id' => Str::uuid(), 'department_id' => $medicalDept->id, 'position_id' => $doctorPosition->id,
                'direct_manager_id' => $gmEmployee->id, 'hire_date' => '2022-06-15', 'actual_start_date' => '2022-06-15',
                'employment_type' => 'full_time', 'status' => 'active',
                'first_name' => 'Khalid', 'last_name' => 'Al-Otaibi', 'first_name_ar' => 'خالد', 'last_name_ar' => 'العتيبي',
                'gender' => 'male', 'date_of_birth' => '1985-11-10', 'national_id' => '1077665544', 'id_type' => 'national_id',
                'nationality' => 'Saudi', 'nationality_ar' => 'سعودي', 'email' => 'dr.khalid@medical-erp.com', 'phone' => '0512345003',
            ]
        );

        // ممرضة
        $nurseEmployee = Employee::firstOrCreate(
            ['employee_number' => '3001'],
            [
                'id' => Str::uuid(), 'department_id' => $nursingDept->id, 'position_id' => $nursePosition->id,
                'direct_manager_id' => $hrEmployee->id, 'hire_date' => '2023-01-10', 'actual_start_date' => '2023-01-10',
                'employment_type' => 'full_time', 'status' => 'active',
                'first_name' => 'Maria', 'last_name' => 'Santos', 'first_name_ar' => 'ماريا', 'last_name_ar' => 'سانتوس',
                'gender' => 'female', 'date_of_birth' => '1992-04-25', 'national_id' => '2399887766', 'id_type' => 'iqama',
                'nationality' => 'Filipino', 'nationality_ar' => 'فلبينية', 'email' => 'maria.santos@medical-erp.com', 'phone' => '0512345004',
            ]
        );

        // ─── 4. حسابات المستخدمين ───

        User::firstOrCreate(
            ['username' => '1001'],
            [
                'id' => Str::uuid(), 'email' => 'gm@medical-erp.com', 'password' => Hash::make('10010512345001'),
                'phone' => '0512345001', 'full_name' => 'Abdullah Al-Rashid', 'full_name_ar' => 'عبدالله الراشد',
                'user_type' => 'super_admin', 'employee_id' => $gmEmployee->id, 'preferred_language' => 'ar', 'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['username' => '1002'],
            [
                'id' => Str::uuid(), 'email' => 'hr@medical-erp.com', 'password' => Hash::make('10020512345002'),
                'phone' => '0512345002', 'full_name' => 'Nora Al-Fahd', 'full_name_ar' => 'نورة الفهد',
                'user_type' => 'hr_manager', 'employee_id' => $hrEmployee->id, 'preferred_language' => 'ar', 'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['username' => '2001'],
            [
                'id' => Str::uuid(), 'email' => 'dr.khalid@medical-erp.com', 'password' => Hash::make('20010512345003'),
                'phone' => '0512345003', 'full_name' => 'Khalid Al-Otaibi', 'full_name_ar' => 'خالد العتيبي',
                'user_type' => 'employee', 'employee_id' => $doctorEmployee->id, 'preferred_language' => 'ar', 'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['username' => '3001'],
            [
                'id' => Str::uuid(), 'email' => 'maria.santos@medical-erp.com', 'password' => Hash::make('30010512345004'),
                'phone' => '0512345004', 'full_name' => 'Maria Santos', 'full_name_ar' => 'ماريا سانتوس',
                'user_type' => 'employee', 'employee_id' => $nurseEmployee->id, 'preferred_language' => 'en', 'is_active' => true,
            ]
        );

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════╗');
        $this->command->info('║    ✅ Foundation data seeded successfully!           ║');
        $this->command->info('╠══════════════════════════════════════════════════════╣');
        $this->command->info('║  Test Accounts (employee_number + phone):           ║');
        $this->command->info('║                                                      ║');
        $this->command->info('║  🔑 Super Admin:  1001  /  0512345001               ║');
        $this->command->info('║  🔑 HR Manager:   1002  /  0512345002               ║');
        $this->command->info('║  🔑 Doctor:       2001  /  0512345003               ║');
        $this->command->info('║  🔑 Nurse:        3001  /  0512345004               ║');
        $this->command->info('╚══════════════════════════════════════════════════════╝');
    }
}
