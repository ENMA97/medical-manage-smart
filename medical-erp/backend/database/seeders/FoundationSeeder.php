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
        $hrDept = Department::create([
            'id' => Str::uuid(),
            'code' => 'HR',
            'name' => 'Human Resources',
            'name_ar' => 'الموارد البشرية',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $adminDept = Department::create([
            'id' => Str::uuid(),
            'code' => 'ADMIN',
            'name' => 'Administration',
            'name_ar' => 'الإدارة العامة',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $medicalDept = Department::create([
            'id' => Str::uuid(),
            'code' => 'MED',
            'name' => 'Medical Department',
            'name_ar' => 'القسم الطبي',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $nursingDept = Department::create([
            'id' => Str::uuid(),
            'code' => 'NURS',
            'name' => 'Nursing Department',
            'name_ar' => 'قسم التمريض',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $financeDept = Department::create([
            'id' => Str::uuid(),
            'code' => 'FIN',
            'name' => 'Finance',
            'name_ar' => 'المالية',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $itDept = Department::create([
            'id' => Str::uuid(),
            'code' => 'IT',
            'name' => 'Information Technology',
            'name_ar' => 'تقنية المعلومات',
            'is_active' => true,
            'sort_order' => 6,
        ]);

        // ─── 2. المسميات الوظيفية ───
        $gmPosition = Position::create([
            'id' => Str::uuid(),
            'code' => 'GM',
            'title' => 'General Manager',
            'title_ar' => 'المدير العام',
            'department_id' => $adminDept->id,
            'category' => 'administrative',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $hrManagerPosition = Position::create([
            'id' => Str::uuid(),
            'code' => 'HRM',
            'title' => 'HR Manager',
            'title_ar' => 'مدير الموارد البشرية',
            'department_id' => $hrDept->id,
            'category' => 'administrative',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $doctorPosition = Position::create([
            'id' => Str::uuid(),
            'code' => 'DOC',
            'title' => 'Doctor',
            'title_ar' => 'طبيب',
            'department_id' => $medicalDept->id,
            'category' => 'medical',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $nursePosition = Position::create([
            'id' => Str::uuid(),
            'code' => 'NRS',
            'title' => 'Nurse',
            'title_ar' => 'ممرض/ة',
            'department_id' => $nursingDept->id,
            'category' => 'medical',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        Position::create([
            'id' => Str::uuid(),
            'code' => 'ACC',
            'title' => 'Accountant',
            'title_ar' => 'محاسب',
            'department_id' => $financeDept->id,
            'category' => 'administrative',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        Position::create([
            'id' => Str::uuid(),
            'code' => 'DEV',
            'title' => 'Software Developer',
            'title_ar' => 'مطور برمجيات',
            'department_id' => $itDept->id,
            'category' => 'technical',
            'is_active' => true,
            'sort_order' => 6,
        ]);

        Position::create([
            'id' => Str::uuid(),
            'code' => 'ADMN',
            'title' => 'Admin Manager',
            'title_ar' => 'المدير الإداري',
            'department_id' => $adminDept->id,
            'category' => 'administrative',
            'is_active' => true,
            'sort_order' => 7,
        ]);

        // ─── 3. الموظفون ───

        // المدير العام (Super Admin)
        $gmEmployee = Employee::create([
            'id' => Str::uuid(),
            'employee_number' => '1001',
            'department_id' => $adminDept->id,
            'position_id' => $gmPosition->id,
            'hire_date' => '2020-01-01',
            'actual_start_date' => '2020-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'first_name' => 'Abdullah',
            'last_name' => 'Al-Rashid',
            'first_name_ar' => 'عبدالله',
            'last_name_ar' => 'الراشد',
            'gender' => 'male',
            'date_of_birth' => '1980-05-15',
            'national_id' => '1099887766',
            'id_type' => 'national_id',
            'nationality' => 'Saudi',
            'nationality_ar' => 'سعودي',
            'email' => 'gm@medical-erp.com',
            'phone' => '0512345001',
        ]);

        // مدير الموارد البشرية
        $hrEmployee = Employee::create([
            'id' => Str::uuid(),
            'employee_number' => '1002',
            'department_id' => $hrDept->id,
            'position_id' => $hrManagerPosition->id,
            'direct_manager_id' => $gmEmployee->id,
            'hire_date' => '2021-03-01',
            'actual_start_date' => '2021-03-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'first_name' => 'Nora',
            'last_name' => 'Al-Fahd',
            'first_name_ar' => 'نورة',
            'last_name_ar' => 'الفهد',
            'gender' => 'female',
            'date_of_birth' => '1990-08-20',
            'national_id' => '1088776655',
            'id_type' => 'national_id',
            'nationality' => 'Saudi',
            'nationality_ar' => 'سعودية',
            'email' => 'hr@medical-erp.com',
            'phone' => '0512345002',
        ]);

        // طبيب (لاختبار مسار الإجازات)
        $doctorEmployee = Employee::create([
            'id' => Str::uuid(),
            'employee_number' => '2001',
            'department_id' => $medicalDept->id,
            'position_id' => $doctorPosition->id,
            'direct_manager_id' => $gmEmployee->id,
            'hire_date' => '2022-06-15',
            'actual_start_date' => '2022-06-15',
            'employment_type' => 'full_time',
            'status' => 'active',
            'first_name' => 'Khalid',
            'last_name' => 'Al-Otaibi',
            'first_name_ar' => 'خالد',
            'last_name_ar' => 'العتيبي',
            'gender' => 'male',
            'date_of_birth' => '1985-11-10',
            'national_id' => '1077665544',
            'id_type' => 'national_id',
            'nationality' => 'Saudi',
            'nationality_ar' => 'سعودي',
            'email' => 'dr.khalid@medical-erp.com',
            'phone' => '0512345003',
        ]);

        // ممرضة
        $nurseEmployee = Employee::create([
            'id' => Str::uuid(),
            'employee_number' => '3001',
            'department_id' => $nursingDept->id,
            'position_id' => $nursePosition->id,
            'direct_manager_id' => $hrEmployee->id,
            'hire_date' => '2023-01-10',
            'actual_start_date' => '2023-01-10',
            'employment_type' => 'full_time',
            'status' => 'active',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'first_name_ar' => 'ماريا',
            'last_name_ar' => 'سانتوس',
            'gender' => 'female',
            'date_of_birth' => '1992-04-25',
            'national_id' => '2399887766',
            'id_type' => 'iqama',
            'nationality' => 'Filipino',
            'nationality_ar' => 'فلبينية',
            'email' => 'maria.santos@medical-erp.com',
            'phone' => '0512345004',
        ]);

        // ─── 4. حسابات المستخدمين ───

        User::create([
            'id' => Str::uuid(),
            'username' => '1001',
            'email' => 'gm@medical-erp.com',
            'password' => Hash::make('10010512345001'),
            'phone' => '0512345001',
            'full_name' => 'Abdullah Al-Rashid',
            'full_name_ar' => 'عبدالله الراشد',
            'user_type' => 'super_admin',
            'employee_id' => $gmEmployee->id,
            'preferred_language' => 'ar',
            'is_active' => true,
        ]);

        User::create([
            'id' => Str::uuid(),
            'username' => '1002',
            'email' => 'hr@medical-erp.com',
            'password' => Hash::make('10020512345002'),
            'phone' => '0512345002',
            'full_name' => 'Nora Al-Fahd',
            'full_name_ar' => 'نورة الفهد',
            'user_type' => 'hr_manager',
            'employee_id' => $hrEmployee->id,
            'preferred_language' => 'ar',
            'is_active' => true,
        ]);

        User::create([
            'id' => Str::uuid(),
            'username' => '2001',
            'email' => 'dr.khalid@medical-erp.com',
            'password' => Hash::make('20010512345003'),
            'phone' => '0512345003',
            'full_name' => 'Khalid Al-Otaibi',
            'full_name_ar' => 'خالد العتيبي',
            'user_type' => 'employee',
            'employee_id' => $doctorEmployee->id,
            'preferred_language' => 'ar',
            'is_active' => true,
        ]);

        User::create([
            'id' => Str::uuid(),
            'username' => '3001',
            'email' => 'maria.santos@medical-erp.com',
            'password' => Hash::make('30010512345004'),
            'phone' => '0512345004',
            'full_name' => 'Maria Santos',
            'full_name_ar' => 'ماريا سانتوس',
            'user_type' => 'employee',
            'employee_id' => $nurseEmployee->id,
            'preferred_language' => 'en',
            'is_active' => true,
        ]);

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
