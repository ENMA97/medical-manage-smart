<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * تعبئة قاعدة بيانات التطبيق
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║     Medical ERP Smart - Database Seeding              ║');
        $this->command->info('║     نظام تخطيط الموارد الطبية - تعبئة البيانات        ║');
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
        $this->command->info('');

        // وحدة الإجازات
        $this->call(LeaveSeeder::class);

        // يمكن إضافة وحدات أخرى هنا لاحقاً
        // $this->call(HRSeeder::class);
        // $this->call(InventorySeeder::class);
        // $this->call(RosterSeeder::class);
        // $this->call(FinanceSeeder::class);
        // $this->call(PayrollSeeder::class);
        // $this->call(SystemSeeder::class);

        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║     ✓ Database seeding completed successfully!        ║');
        $this->command->info('║     ✓ اكتملت تعبئة قاعدة البيانات بنجاح!              ║');
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
        $this->command->info('');
    }
}
