<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RosterSeeder extends Seeder
{
    /**
     * تعبئة بيانات وحدة الجدولة
     * Seed the roster module data.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  تعبئة بيانات وحدة الجدولة');
        $this->command->info('  Roster Module Seeding');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');

        // 1. أنماط الورديات
        $this->seedShiftPatterns();

        // 2. قواعد التحقق
        $this->seedValidationRules();

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✓ اكتملت تعبئة وحدة الجدولة بنجاح');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
    }

    /**
     * تعبئة أنماط الورديات
     * أنماط شائعة في المنشآت الطبية السعودية
     */
    protected function seedShiftPatterns(): void
    {
        $this->command->info('1. إنشاء أنماط الورديات...');

        $patterns = [
            // الورديات الصباحية
            [
                'code' => 'MORNING_8H',
                'name_ar' => 'صباحي - 8 ساعات',
                'name_en' => 'Morning - 8 Hours',
                'description' => 'وردية صباحية قياسية للموظفين الإداريين',
                'type' => 'morning',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'break_duration_minutes' => 60,
                'scheduled_hours' => 7.00,
                'color_code' => '#3B82F6', // أزرق
            ],
            [
                'code' => 'MORNING_12H',
                'name_ar' => 'صباحي - 12 ساعة',
                'name_en' => 'Morning - 12 Hours',
                'description' => 'وردية صباحية طويلة للتمريض',
                'type' => 'morning',
                'start_time' => '07:00:00',
                'end_time' => '19:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'break_duration_minutes' => 60,
                'scheduled_hours' => 11.00,
                'color_code' => '#2563EB', // أزرق غامق
            ],
            [
                'code' => 'MORNING_CLINIC',
                'name_ar' => 'عيادات صباحية',
                'name_en' => 'Morning Clinic',
                'description' => 'وردية العيادات الخارجية الصباحية',
                'type' => 'morning',
                'start_time' => '08:30:00',
                'end_time' => '13:30:00',
                'break_start' => null,
                'break_end' => null,
                'break_duration_minutes' => 0,
                'scheduled_hours' => 5.00,
                'color_code' => '#60A5FA', // أزرق فاتح
            ],

            // الورديات المسائية
            [
                'code' => 'EVENING_8H',
                'name_ar' => 'مسائي - 8 ساعات',
                'name_en' => 'Evening - 8 Hours',
                'description' => 'وردية مسائية قياسية',
                'type' => 'evening',
                'start_time' => '16:00:00',
                'end_time' => '00:00:00',
                'break_start' => '19:00:00',
                'break_end' => '20:00:00',
                'break_duration_minutes' => 60,
                'scheduled_hours' => 7.00,
                'color_code' => '#F59E0B', // برتقالي
            ],
            [
                'code' => 'EVENING_CLINIC',
                'name_ar' => 'عيادات مسائية',
                'name_en' => 'Evening Clinic',
                'description' => 'وردية العيادات الخارجية المسائية',
                'type' => 'evening',
                'start_time' => '16:00:00',
                'end_time' => '21:00:00',
                'break_start' => null,
                'break_end' => null,
                'break_duration_minutes' => 0,
                'scheduled_hours' => 5.00,
                'color_code' => '#FBBF24', // أصفر
            ],

            // الورديات الليلية
            [
                'code' => 'NIGHT_8H',
                'name_ar' => 'ليلي - 8 ساعات',
                'name_en' => 'Night - 8 Hours',
                'description' => 'وردية ليلية قياسية',
                'type' => 'night',
                'start_time' => '23:00:00',
                'end_time' => '07:00:00',
                'break_start' => '03:00:00',
                'break_end' => '04:00:00',
                'break_duration_minutes' => 60,
                'scheduled_hours' => 7.00,
                'color_code' => '#6366F1', // بنفسجي
            ],
            [
                'code' => 'NIGHT_12H',
                'name_ar' => 'ليلي - 12 ساعة',
                'name_en' => 'Night - 12 Hours',
                'description' => 'وردية ليلية طويلة للتمريض',
                'type' => 'night',
                'start_time' => '19:00:00',
                'end_time' => '07:00:00',
                'break_start' => '01:00:00',
                'break_end' => '02:00:00',
                'break_duration_minutes' => 60,
                'scheduled_hours' => 11.00,
                'color_code' => '#4F46E5', // بنفسجي غامق
            ],

            // الورديات المتقطعة
            [
                'code' => 'SPLIT_CLINIC',
                'name_ar' => 'متقطع - عيادات',
                'name_en' => 'Split - Clinic',
                'description' => 'وردية متقطعة للعيادات (صباحي ومسائي)',
                'type' => 'split',
                'start_time' => '09:00:00',
                'end_time' => '21:00:00',
                'break_start' => '13:00:00',
                'break_end' => '17:00:00',
                'break_duration_minutes' => 240,
                'scheduled_hours' => 8.00,
                'color_code' => '#10B981', // أخضر
            ],

            // ورديات خاصة
            [
                'code' => 'ON_CALL',
                'name_ar' => 'تحت الطلب',
                'name_en' => 'On Call',
                'description' => 'تحت الطلب - للطوارئ',
                'type' => 'morning', // يستخدم كنوع افتراضي
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'break_start' => null,
                'break_end' => null,
                'break_duration_minutes' => 0,
                'scheduled_hours' => 0,
                'color_code' => '#EF4444', // أحمر
            ],
            [
                'code' => 'EMERGENCY_24H',
                'name_ar' => 'طوارئ - 24 ساعة',
                'name_en' => 'Emergency - 24 Hours',
                'description' => 'وردية طوارئ كاملة',
                'type' => 'morning',
                'start_time' => '08:00:00',
                'end_time' => '08:00:00', // اليوم التالي
                'break_start' => null,
                'break_end' => null,
                'break_duration_minutes' => 0,
                'scheduled_hours' => 24.00,
                'color_code' => '#DC2626', // أحمر غامق
            ],
        ];

        $now = now();
        foreach ($patterns as &$pattern) {
            $pattern['id'] = Str::uuid();
            $pattern['is_active'] = true;
            $pattern['created_at'] = $now;
            $pattern['updated_at'] = $now;
        }

        DB::table('shift_patterns')->insertOrIgnore($patterns);

        $this->command->info('   ✓ تم إنشاء ' . count($patterns) . ' نمط وردية');

        // عرض ملخص الورديات
        $this->command->table(
            ['الكود', 'الاسم', 'النوع', 'من', 'إلى', 'الساعات'],
            collect($patterns)->map(fn($p) => [
                $p['code'],
                $p['name_ar'],
                $p['type'],
                substr($p['start_time'], 0, 5),
                substr($p['end_time'], 0, 5),
                $p['scheduled_hours'] . ' ساعة',
            ])->toArray()
        );
    }

    /**
     * تعبئة قواعد التحقق من الجدولة
     * قواعد نظام العمل السعودي ومعايير الرعاية الصحية
     */
    protected function seedValidationRules(): void
    {
        $this->command->info('2. إنشاء قواعد التحقق...');

        $rules = [
            // قواعد ساعات العمل
            [
                'code' => 'MAX_HOURS_WEEK',
                'name_ar' => 'الحد الأقصى لساعات العمل الأسبوعية',
                'name_en' => 'Maximum Weekly Hours',
                'description' => 'لا يجوز أن تتجاوز ساعات العمل 48 ساعة أسبوعياً (نظام العمل السعودي)',
                'rule_type' => 'max_hours',
                'parameters' => json_encode([
                    'max_hours_per_week' => 48,
                    'include_overtime' => false,
                ]),
                'severity' => 'error',
            ],
            [
                'code' => 'MAX_HOURS_DAY',
                'name_ar' => 'الحد الأقصى لساعات العمل اليومية',
                'name_en' => 'Maximum Daily Hours',
                'description' => 'لا يجوز أن تتجاوز ساعات العمل 8 ساعات يومياً (أو 10 للورديات الخاصة)',
                'rule_type' => 'max_hours',
                'parameters' => json_encode([
                    'max_hours_per_day' => 8,
                    'max_hours_special_shift' => 12,
                ]),
                'severity' => 'warning',
            ],

            // قواعد الراحة
            [
                'code' => 'MIN_REST_BETWEEN',
                'name_ar' => 'الحد الأدنى للراحة بين الورديات',
                'name_en' => 'Minimum Rest Between Shifts',
                'description' => 'يجب أن لا تقل فترة الراحة بين وردية وأخرى عن 11 ساعة',
                'rule_type' => 'min_rest',
                'parameters' => json_encode([
                    'min_hours_between_shifts' => 11,
                ]),
                'severity' => 'error',
            ],
            [
                'code' => 'WEEKLY_REST_DAY',
                'name_ar' => 'يوم الراحة الأسبوعي',
                'name_en' => 'Weekly Rest Day',
                'description' => 'يحق للموظف راحة أسبوعية لا تقل عن 24 ساعة متواصلة',
                'rule_type' => 'weekly_rest',
                'parameters' => json_encode([
                    'min_consecutive_rest_hours' => 24,
                    'per_days' => 7,
                ]),
                'severity' => 'error',
            ],

            // قواعد الورديات المتتالية
            [
                'code' => 'CONSECUTIVE_NIGHTS',
                'name_ar' => 'الورديات الليلية المتتالية',
                'name_en' => 'Consecutive Night Shifts',
                'description' => 'لا يُنصح بأكثر من 4 ورديات ليلية متتالية',
                'rule_type' => 'consecutive_days',
                'parameters' => json_encode([
                    'shift_type' => 'night',
                    'max_consecutive' => 4,
                ]),
                'severity' => 'warning',
            ],
            [
                'code' => 'CONSECUTIVE_WORK_DAYS',
                'name_ar' => 'أيام العمل المتتالية',
                'name_en' => 'Consecutive Work Days',
                'description' => 'لا يجوز العمل أكثر من 6 أيام متتالية دون راحة',
                'rule_type' => 'consecutive_days',
                'parameters' => json_encode([
                    'max_consecutive_days' => 6,
                ]),
                'severity' => 'error',
            ],

            // قواعد الوقت الإضافي
            [
                'code' => 'MAX_OVERTIME_MONTH',
                'name_ar' => 'الحد الأقصى للوقت الإضافي الشهري',
                'name_en' => 'Maximum Monthly Overtime',
                'description' => 'لا يجوز أن يتجاوز الوقت الإضافي 720 ساعة سنوياً (60 ساعة شهرياً)',
                'rule_type' => 'max_overtime',
                'parameters' => json_encode([
                    'max_overtime_per_month' => 60,
                    'max_overtime_per_year' => 720,
                ]),
                'severity' => 'warning',
            ],

            // قواعد التغطية
            [
                'code' => 'MIN_STAFF_COVERAGE',
                'name_ar' => 'الحد الأدنى للتغطية',
                'name_en' => 'Minimum Staff Coverage',
                'description' => 'يجب توفر حد أدنى من الموظفين في كل وردية',
                'rule_type' => 'min_coverage',
                'parameters' => json_encode([
                    'check_per_department' => true,
                    'alert_percentage' => 80, // تنبيه عند أقل من 80%
                ]),
                'severity' => 'warning',
            ],

            // قواعد رمضان
            [
                'code' => 'RAMADAN_HOURS',
                'name_ar' => 'ساعات العمل في رمضان',
                'name_en' => 'Ramadan Working Hours',
                'description' => 'تخفيض ساعات العمل للمسلمين في رمضان إلى 6 ساعات',
                'rule_type' => 'special_period',
                'parameters' => json_encode([
                    'period_type' => 'ramadan',
                    'max_hours_per_day' => 6,
                    'applies_to' => 'muslim_employees',
                ]),
                'severity' => 'info',
            ],
        ];

        $now = now();
        foreach ($rules as &$rule) {
            $rule['id'] = Str::uuid();
            $rule['department_id'] = null; // ينطبق على الجميع
            $rule['position_id'] = null;
            $rule['is_active'] = true;
            $rule['created_at'] = $now;
            $rule['updated_at'] = $now;
        }

        DB::table('roster_validation_rules')->insertOrIgnore($rules);

        $this->command->info('   ✓ تم إنشاء ' . count($rules) . ' قاعدة تحقق');

        // عرض ملخص القواعد
        $this->command->table(
            ['الكود', 'القاعدة', 'النوع', 'الأهمية'],
            collect($rules)->map(fn($r) => [
                $r['code'],
                Str::limit($r['name_ar'], 30),
                $r['rule_type'],
                match($r['severity']) {
                    'error' => '❌ خطأ',
                    'warning' => '⚠️ تحذير',
                    'info' => 'ℹ️ معلومة',
                },
            ])->toArray()
        );
    }
}
