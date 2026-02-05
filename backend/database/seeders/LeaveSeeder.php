<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
{
    /**
     * تشغيل جميع seeders وحدة الإجازات
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  تعبئة بيانات وحدة الإجازات');
        $this->command->info('  Leave Module Seeding');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');

        // 1. أنواع الإجازات
        $this->command->info('1. إنشاء أنواع الإجازات...');
        $this->call(LeaveTypeSeeder::class);

        // 2. سياسات الإجازات
        $this->command->info('');
        $this->command->info('2. إنشاء سياسات الإجازات...');
        $this->call(LeavePolicySeeder::class);

        // 3. الإجازات الرسمية
        $this->command->info('');
        $this->command->info('3. إنشاء الإجازات الرسمية...');
        $this->call(PublicHolidaySeeder::class);

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✓ اكتملت تعبئة وحدة الإجازات بنجاح');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
    }
}
