<?php

namespace Database\Seeders;

use App\Models\Leave\PublicHoliday;
use Illuminate\Database\Seeder;

class PublicHolidaySeeder extends Seeder
{
    /**
     * تعبئة الإجازات الرسمية في المملكة العربية السعودية
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = (int) date('Y');

        $holidays = [
            // عيد الفطر المبارك (تقريبي - يعتمد على رؤية الهلال)
            [
                'name_ar' => 'عيد الفطر المبارك',
                'name_en' => 'Eid Al-Fitr',
                'date' => "{$currentYear}-04-10", // تاريخ تقريبي
                'hijri_date' => '1 شوال',
                'calendar_type' => 'hijri',
                'is_recurring' => true,
                'number_of_days' => 4,
                'applies_to_all' => true,
                'is_active' => true,
            ],

            // عيد الأضحى المبارك (تقريبي - يعتمد على رؤية الهلال)
            [
                'name_ar' => 'عيد الأضحى المبارك',
                'name_en' => 'Eid Al-Adha',
                'date' => "{$currentYear}-06-17", // تاريخ تقريبي
                'hijri_date' => '10 ذو الحجة',
                'calendar_type' => 'hijri',
                'is_recurring' => true,
                'number_of_days' => 4,
                'applies_to_all' => true,
                'is_active' => true,
            ],

            // اليوم الوطني السعودي
            [
                'name_ar' => 'اليوم الوطني السعودي',
                'name_en' => 'Saudi National Day',
                'date' => "{$currentYear}-09-23",
                'hijri_date' => null,
                'calendar_type' => 'gregorian',
                'is_recurring' => true,
                'number_of_days' => 1,
                'applies_to_all' => true,
                'is_active' => true,
            ],

            // يوم التأسيس
            [
                'name_ar' => 'يوم التأسيس',
                'name_en' => 'Founding Day',
                'date' => "{$currentYear}-02-22",
                'hijri_date' => null,
                'calendar_type' => 'gregorian',
                'is_recurring' => true,
                'number_of_days' => 1,
                'applies_to_all' => true,
                'is_active' => true,
            ],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'name_ar' => $holiday['name_ar'],
                    'date' => $holiday['date'],
                ],
                $holiday
            );
        }

        // إضافة إجازات السنة القادمة
        $nextYear = $currentYear + 1;
        $nextYearHolidays = [
            [
                'name_ar' => 'اليوم الوطني السعودي',
                'name_en' => 'Saudi National Day',
                'date' => "{$nextYear}-09-23",
                'calendar_type' => 'gregorian',
                'is_recurring' => true,
                'number_of_days' => 1,
                'applies_to_all' => true,
                'is_active' => true,
            ],
            [
                'name_ar' => 'يوم التأسيس',
                'name_en' => 'Founding Day',
                'date' => "{$nextYear}-02-22",
                'calendar_type' => 'gregorian',
                'is_recurring' => true,
                'number_of_days' => 1,
                'applies_to_all' => true,
                'is_active' => true,
            ],
        ];

        foreach ($nextYearHolidays as $holiday) {
            PublicHoliday::updateOrCreate(
                [
                    'name_ar' => $holiday['name_ar'],
                    'date' => $holiday['date'],
                ],
                $holiday
            );
        }

        $this->command->info('✓ تم إنشاء الإجازات الرسمية للعام الحالي والقادم');
    }
}
