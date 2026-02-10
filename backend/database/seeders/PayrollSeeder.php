<?php

namespace Database\Seeders;

use App\Models\Payroll\PayrollSettings;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    /**
     * تعبئة بيانات وحدة الرواتب
     * Seed the payroll module data.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  تعبئة بيانات وحدة الرواتب');
        $this->command->info('  Payroll Module Seeding');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');

        // 1. إعدادات الرواتب
        $this->seedPayrollSettings();

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✓ اكتملت تعبئة وحدة الرواتب بنجاح');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
    }

    /**
     * تعبئة إعدادات الرواتب
     */
    protected function seedPayrollSettings(): void
    {
        $this->command->info('1. إنشاء إعدادات الرواتب...');

        // استخدام الدالة الموجودة في Model
        PayrollSettings::seedDefaults();

        $count = PayrollSettings::count();
        $this->command->info("   ✓ تم إنشاء {$count} إعداد");

        // عرض الإعدادات الرئيسية
        $this->command->table(
            ['الإعداد', 'القيمة', 'الوصف'],
            [
                ['gosi_employee_rate', '9.75%', 'نسبة التأمينات على الموظف السعودي'],
                ['gosi_employer_rate', '11.75%', 'نسبة التأمينات على صاحب العمل (سعودي)'],
                ['gosi_max_salary', '45,000 ريال', 'الحد الأقصى للراتب الخاضع للتأمينات'],
                ['overtime_rate_normal', '1.5x', 'معامل الوقت الإضافي العادي'],
                ['working_days_per_month', '30 يوم', 'أيام العمل الشهرية'],
            ]
        );
    }
}
