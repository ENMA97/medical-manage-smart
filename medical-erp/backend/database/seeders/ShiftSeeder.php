<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * الورديات الافتراضية للمنشآت الطبية
 */
class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'code' => 'SH-ADMIN',
                'name' => 'Administrative Shift',
                'name_ar' => 'الوردية الإدارية',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'break_minutes' => 60,
                'grace_period_minutes' => 15,
                'is_overnight' => false,
                'sort_order' => 1,
                'description' => 'وردية الموظفين الإداريين',
            ],
            [
                'code' => 'SH-MORNING',
                'name' => 'Morning Shift',
                'name_ar' => 'الوردية الصباحية',
                'start_time' => '08:00',
                'end_time' => '16:00',
                'break_minutes' => 30,
                'grace_period_minutes' => 10,
                'is_overnight' => false,
                'sort_order' => 2,
                'description' => 'الوردية الصباحية للكوادر الطبية',
            ],
            [
                'code' => 'SH-EVENING',
                'name' => 'Evening Shift',
                'name_ar' => 'الوردية المسائية',
                'start_time' => '16:00',
                'end_time' => '00:00',
                'break_minutes' => 30,
                'grace_period_minutes' => 10,
                'is_overnight' => true,
                'sort_order' => 3,
                'description' => 'الوردية المسائية للكوادر الطبية',
            ],
            [
                'code' => 'SH-NIGHT',
                'name' => 'Night Shift',
                'name_ar' => 'الوردية الليلية',
                'start_time' => '00:00',
                'end_time' => '08:00',
                'break_minutes' => 30,
                'grace_period_minutes' => 10,
                'is_overnight' => false,
                'sort_order' => 4,
                'description' => 'الوردية الليلية للكوادر الطبية',
            ],
        ];

        foreach ($shifts as $shift) {
            // WithoutModelEvents يعطّل حدث توليد UUID — يجب تمرير المعرف صراحة
            Shift::firstOrCreate(['code' => $shift['code']], $shift + ['id' => Str::uuid()]);
        }
    }
}
