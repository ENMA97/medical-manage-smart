<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemSeeder extends Seeder
{
    /**
     * تعبئة بيانات النظام الأساسية
     * Seed the system data.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  تعبئة بيانات النظام');
        $this->command->info('  System Module Seeding');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');

        // 1. الصلاحيات
        $this->seedPermissions();

        // 2. الأدوار
        $this->seedRoles();

        // 3. ربط الصلاحيات بالأدوار
        $this->seedRolePermissions();

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✓ اكتملت تعبئة بيانات النظام');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
    }

    /**
     * تعبئة الصلاحيات
     */
    protected function seedPermissions(): void
    {
        $this->command->info('1. إنشاء الصلاحيات...');

        $permissions = [
            // الموارد البشرية
            ['code' => 'hr.view', 'module' => 'hr', 'name_ar' => 'عرض بيانات الموظفين'],
            ['code' => 'hr.create', 'module' => 'hr', 'name_ar' => 'إضافة موظف'],
            ['code' => 'hr.edit', 'module' => 'hr', 'name_ar' => 'تعديل بيانات الموظفين'],
            ['code' => 'hr.delete', 'module' => 'hr', 'name_ar' => 'حذف موظف'],
            ['code' => 'hr.contracts', 'module' => 'hr', 'name_ar' => 'إدارة العقود'],
            ['code' => 'hr.custodies', 'module' => 'hr', 'name_ar' => 'إدارة العهد'],
            ['code' => 'hr.clearance', 'module' => 'hr', 'name_ar' => 'إدارة إخلاء الطرف'],

            // الرواتب
            ['code' => 'payroll.view', 'module' => 'payroll', 'name_ar' => 'عرض الرواتب'],
            ['code' => 'payroll.calculate', 'module' => 'payroll', 'name_ar' => 'حساب الرواتب'],
            ['code' => 'payroll.approve', 'module' => 'payroll', 'name_ar' => 'اعتماد الرواتب'],
            ['code' => 'payroll.pay', 'module' => 'payroll', 'name_ar' => 'تسجيل الدفع'],
            ['code' => 'payroll.wps', 'module' => 'payroll', 'name_ar' => 'توليد ملفات WPS'],
            ['code' => 'payroll.loans', 'module' => 'payroll', 'name_ar' => 'إدارة السلف'],
            ['code' => 'payroll.settings', 'module' => 'payroll', 'name_ar' => 'إعدادات الرواتب'],

            // الجدولة
            ['code' => 'roster.view', 'module' => 'roster', 'name_ar' => 'عرض الجداول'],
            ['code' => 'roster.create', 'module' => 'roster', 'name_ar' => 'إنشاء جدول'],
            ['code' => 'roster.edit', 'module' => 'roster', 'name_ar' => 'تعديل الجدول'],
            ['code' => 'roster.publish', 'module' => 'roster', 'name_ar' => 'نشر الجدول'],
            ['code' => 'roster.attendance', 'module' => 'roster', 'name_ar' => 'إدارة الحضور'],

            // المخزون
            ['code' => 'inventory.view', 'module' => 'inventory', 'name_ar' => 'عرض المخزون'],
            ['code' => 'inventory.receive', 'module' => 'inventory', 'name_ar' => 'استلام أصناف'],
            ['code' => 'inventory.issue', 'module' => 'inventory', 'name_ar' => 'صرف أصناف'],
            ['code' => 'inventory.transfer', 'module' => 'inventory', 'name_ar' => 'تحويل بين المستودعات'],
            ['code' => 'inventory.adjust', 'module' => 'inventory', 'name_ar' => 'تعديل المخزون'],
            ['code' => 'inventory.purchase', 'module' => 'inventory', 'name_ar' => 'طلبات الشراء'],

            // الإجازات
            ['code' => 'leave.view', 'module' => 'leave', 'name_ar' => 'عرض الإجازات'],
            ['code' => 'leave.request', 'module' => 'leave', 'name_ar' => 'طلب إجازة'],
            ['code' => 'leave.approve_supervisor', 'module' => 'leave', 'name_ar' => 'موافقة المشرف'],
            ['code' => 'leave.approve_manager', 'module' => 'leave', 'name_ar' => 'موافقة المدير'],
            ['code' => 'leave.approve_hr', 'module' => 'leave', 'name_ar' => 'تعميد الموارد البشرية'],
            ['code' => 'leave.approve_gm', 'module' => 'leave', 'name_ar' => 'موافقة المدير العام'],
            ['code' => 'leave.settings', 'module' => 'leave', 'name_ar' => 'إعدادات الإجازات'],

            // النظام
            ['code' => 'system.users', 'module' => 'system', 'name_ar' => 'إدارة المستخدمين'],
            ['code' => 'system.roles', 'module' => 'system', 'name_ar' => 'إدارة الأدوار'],
            ['code' => 'system.settings', 'module' => 'system', 'name_ar' => 'إعدادات النظام'],
            ['code' => 'system.audit', 'module' => 'system', 'name_ar' => 'سجل التدقيق'],
            ['code' => 'system.reports', 'module' => 'system', 'name_ar' => 'التقارير'],
        ];

        $now = now();
        foreach ($permissions as &$permission) {
            $permission['id'] = Str::uuid();
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
        }

        DB::table('permissions')->insertOrIgnore($permissions);
        $this->command->info('   ✓ تم إنشاء ' . count($permissions) . ' صلاحية');
    }

    /**
     * تعبئة الأدوار
     */
    protected function seedRoles(): void
    {
        $this->command->info('2. إنشاء الأدوار...');

        $roles = [
            [
                'id' => Str::uuid(),
                'code' => 'super_admin',
                'name_ar' => 'مدير النظام',
                'name_en' => 'Super Admin',
                'description_ar' => 'صلاحيات كاملة على جميع الوحدات',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'code' => 'hr_manager',
                'name_ar' => 'مدير الموارد البشرية',
                'name_en' => 'HR Manager',
                'description_ar' => 'إدارة شؤون الموظفين والعقود',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'code' => 'payroll_manager',
                'name_ar' => 'مدير الرواتب',
                'name_en' => 'Payroll Manager',
                'description_ar' => 'إدارة وحساب واعتماد الرواتب',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'code' => 'finance_manager',
                'name_ar' => 'المدير المالي',
                'name_en' => 'Finance Manager',
                'description_ar' => 'الموافقات المالية والتقارير',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'code' => 'department_manager',
                'name_ar' => 'مدير القسم',
                'name_en' => 'Department Manager',
                'description_ar' => 'إدارة موظفي القسم والجدولة',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'code' => 'inventory_manager',
                'name_ar' => 'مدير المستودعات',
                'name_en' => 'Inventory Manager',
                'description_ar' => 'إدارة المخزون والأصناف',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'code' => 'employee',
                'name_ar' => 'موظف',
                'name_en' => 'Employee',
                'description_ar' => 'صلاحيات الموظف الأساسية',
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        $now = now();
        foreach ($roles as &$role) {
            $role['created_at'] = $now;
            $role['updated_at'] = $now;
        }

        DB::table('roles')->insertOrIgnore($roles);
        $this->command->info('   ✓ تم إنشاء ' . count($roles) . ' دور');
    }

    /**
     * ربط الصلاحيات بالأدوار
     */
    protected function seedRolePermissions(): void
    {
        $this->command->info('3. ربط الصلاحيات بالأدوار...');

        // تعريف الصلاحيات لكل دور
        $rolePermissions = [
            'super_admin' => ['*'], // جميع الصلاحيات

            'hr_manager' => [
                'hr.*',
                'leave.view', 'leave.approve_hr', 'leave.settings',
                'payroll.view',
                'roster.view',
            ],

            'payroll_manager' => [
                'payroll.*',
                'hr.view',
            ],

            'finance_manager' => [
                'payroll.view', 'payroll.approve', 'payroll.pay', 'payroll.wps',
                'inventory.view', 'inventory.purchase',
                'system.reports',
            ],

            'department_manager' => [
                'hr.view',
                'roster.view', 'roster.create', 'roster.edit', 'roster.publish',
                'leave.view', 'leave.approve_supervisor', 'leave.approve_manager',
                'inventory.view', 'inventory.issue',
            ],

            'inventory_manager' => [
                'inventory.*',
            ],

            'employee' => [
                'leave.request', 'leave.view',
                'roster.view',
            ],
        ];

        // جلب الأدوار والصلاحيات
        $roles = DB::table('roles')->pluck('id', 'code');
        $permissions = DB::table('permissions')->pluck('id', 'code');

        $insertData = [];

        foreach ($rolePermissions as $roleCode => $permissionCodes) {
            $roleId = $roles[$roleCode] ?? null;
            if (!$roleId) continue;

            foreach ($permissionCodes as $permCode) {
                if ($permCode === '*') {
                    // جميع الصلاحيات
                    foreach ($permissions as $permId) {
                        $insertData[] = [
                            'role_id' => $roleId,
                            'permission_id' => $permId,
                        ];
                    }
                } elseif (str_ends_with($permCode, '.*')) {
                    // جميع صلاحيات الموديول
                    $module = str_replace('.*', '', $permCode);
                    $modulePerms = DB::table('permissions')
                        ->where('module', $module)
                        ->pluck('id');

                    foreach ($modulePerms as $permId) {
                        $insertData[] = [
                            'role_id' => $roleId,
                            'permission_id' => $permId,
                        ];
                    }
                } else {
                    // صلاحية محددة
                    $permId = $permissions[$permCode] ?? null;
                    if ($permId) {
                        $insertData[] = [
                            'role_id' => $roleId,
                            'permission_id' => $permId,
                        ];
                    }
                }
            }
        }

        // إزالة التكرارات
        $insertData = collect($insertData)->unique(function ($item) {
            return $item['role_id'] . $item['permission_id'];
        })->values()->all();

        DB::table('role_permissions')->insertOrIgnore($insertData);
        $this->command->info('   ✓ تم ربط ' . count($insertData) . ' صلاحية بالأدوار');
    }
}
