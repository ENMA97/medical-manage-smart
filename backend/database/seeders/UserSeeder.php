<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * تعبئة بيانات المستخدمين الأولية
     * Seed initial users data.
     *
     * يجب تغيير كلمة المرور الافتراضية فور تسجيل الدخول الأول
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  تعبئة بيانات المستخدمين');
        $this->command->info('  User Seeding');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');

        // 1. المستخدم الإداري الأول
        $this->seedAdminUser();

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✓ اكتملت تعبئة المستخدمين');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
    }

    /**
     * إنشاء حساب المدير الأول
     */
    protected function seedAdminUser(): void
    {
        $this->command->info('1. إنشاء حساب المدير...');

        $now = now();
        $adminId = Str::uuid();

        // التحقق من عدم وجود المستخدم مسبقاً
        $exists = DB::table('users')
            ->where('username', 'admin')
            ->orWhere('email', 'admin@medical-erp.local')
            ->exists();

        if ($exists) {
            $this->command->warn('   ⚠️ حساب المدير موجود مسبقاً');
            return;
        }

        // إنشاء المستخدم الإداري
        DB::table('users')->insert([
            'id' => $adminId,
            'username' => 'admin',
            'email' => 'admin@medical-erp.local',
            'password' => Hash::make('Admin@2026!'), // يجب تغييرها فوراً
            'name_ar' => 'مدير النظام',
            'name_en' => 'System Administrator',
            'phone' => null,
            'employee_id' => null,
            'is_active' => true,
            'must_change_password' => true, // إجباري تغيير كلمة المرور
            'last_login_at' => null,
            'last_login_ip' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ربط المستخدم بدور super_admin
        $superAdminRole = DB::table('roles')
            ->where('code', 'super_admin')
            ->first();

        if ($superAdminRole) {
            DB::table('user_roles')->insert([
                'user_id' => $adminId,
                'role_id' => $superAdminRole->id,
                'assigned_by' => null, // النظام
                'assigned_at' => $now,
            ]);
            $this->command->info('   ✓ تم ربط المستخدم بدور مدير النظام');
        }

        $this->command->info('   ✓ تم إنشاء حساب المدير');

        // عرض بيانات الدخول
        $this->command->newLine();
        $this->command->warn('   ╔═══════════════════════════════════════════════════════╗');
        $this->command->warn('   ║  بيانات الدخول الأولية - يجب تغييرها فوراً!          ║');
        $this->command->warn('   ║  Initial Credentials - MUST be changed immediately!  ║');
        $this->command->warn('   ╠═══════════════════════════════════════════════════════╣');
        $this->command->warn('   ║  Username: admin                                      ║');
        $this->command->warn('   ║  Password: Admin@2026!                                ║');
        $this->command->warn('   ╚═══════════════════════════════════════════════════════╝');
        $this->command->newLine();
    }
}
