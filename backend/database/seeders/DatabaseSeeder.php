<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * تعبئة قاعدة بيانات التطبيق
     *
     * ترتيب التعبئة مهم بسبب العلاقات بين الجداول:
     * 1. SystemSeeder - الأدوار والصلاحيات (بدون تبعيات)
     * 2. HRSeeder - الأقسام والمناصب (بدون تبعيات)
     * 3. InventorySeeder - المستودعات والفئات (يعتمد على الأقسام)
     * 4. RosterSeeder - أنماط الورديات (يعتمد على الأقسام)
     * 5. PayrollSeeder - إعدادات الرواتب (بدون تبعيات مباشرة)
     * 6. LeaveSeeder - أنواع الإجازات (يعتمد على الأقسام)
     * 7. UserSeeder - المستخدم الإداري الأول (يعتمد على الأدوار)
     */
    public function run(): void
    {
        $startTime = microtime(true);

        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║     Medical ERP Smart - Database Seeding              ║');
        $this->command->info('║     نظام تخطيط الموارد الطبية - تعبئة البيانات        ║');
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
        $this->command->info('');

        // 1. وحدة النظام (الأدوار والصلاحيات)
        $this->command->info('📦 [1/7] System Module...');
        $this->call(SystemSeeder::class);

        // 2. وحدة الموارد البشرية (الأقسام والمناصب)
        $this->command->info('📦 [2/7] HR Module...');
        $this->call(HRSeeder::class);

        // 3. وحدة المخزون (المستودعات والفئات)
        $this->command->info('📦 [3/7] Inventory Module...');
        $this->call(InventorySeeder::class);

        // 4. وحدة الجدولة (أنماط الورديات)
        $this->command->info('📦 [4/7] Roster Module...');
        $this->call(RosterSeeder::class);

        // 5. وحدة الرواتب (الإعدادات)
        $this->command->info('📦 [5/7] Payroll Module...');
        $this->call(PayrollSeeder::class);

        // 6. وحدة الإجازات (أنواع الإجازات والسياسات)
        $this->command->info('📦 [6/7] Leave Module...');
        $this->call(LeaveSeeder::class);

        // 7. المستخدم الإداري الأول
        $this->command->info('📦 [7/7] Admin User...');
        $this->call(UserSeeder::class);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║     ✓ Database seeding completed successfully!        ║');
        $this->command->info('║     ✓ اكتملت تعبئة قاعدة البيانات بنجاح!              ║');
        $this->command->info("║     ⏱️  Duration: {$duration} seconds                    ║");
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
        $this->command->info('');

        // ملخص البيانات المُنشأة
        $this->displaySummary();
    }

    /**
     * عرض ملخص البيانات المُنشأة
     */
    protected function displaySummary(): void
    {
        $this->command->info('📊 Summary / الملخص:');
        $this->command->info('');

        $tables = [
            'users' => 'المستخدمين',
            'permissions' => 'الصلاحيات',
            'roles' => 'الأدوار',
            'departments' => 'الأقسام',
            'positions' => 'المناصب',
            'warehouses' => 'المستودعات',
            'item_categories' => 'فئات الأصناف',
            'shift_patterns' => 'أنماط الورديات',
            'roster_validation_rules' => 'قواعد التحقق',
            'payroll_settings' => 'إعدادات الرواتب',
            'leave_types' => 'أنواع الإجازات',
        ];

        $summary = [];
        foreach ($tables as $table => $label) {
            try {
                $count = \Illuminate\Support\Facades\DB::table($table)->count();
                $summary[] = [$label, $table, $count];
            } catch (\Exception $e) {
                $summary[] = [$label, $table, 'N/A'];
            }
        }

        $this->command->table(
            ['الوحدة', 'Table', 'العدد'],
            $summary
        );
    }
}
