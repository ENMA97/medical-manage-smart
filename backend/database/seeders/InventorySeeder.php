<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventorySeeder extends Seeder
{
    /**
     * تعبئة بيانات وحدة المخزون
     * Seed the inventory module data.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  تعبئة بيانات وحدة المخزون');
        $this->command->info('  Inventory Module Seeding');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');

        // 1. فئات الأصناف
        $this->seedItemCategories();

        // 2. المستودعات
        $this->seedWarehouses();

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✓ اكتملت تعبئة وحدة المخزون بنجاح');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
    }

    /**
     * تعبئة فئات الأصناف
     */
    protected function seedItemCategories(): void
    {
        $this->command->info('1. إنشاء فئات الأصناف...');

        $categories = [
            // الفئات الرئيسية
            [
                'code' => 'MED',
                'name_ar' => 'الأدوية',
                'name_en' => 'Medications',
                'description' => 'جميع أنواع الأدوية والعقاقير',
                'parent_code' => null,
                'is_controlled' => false,
            ],
            [
                'code' => 'MED_ORAL',
                'name_ar' => 'أدوية فموية',
                'name_en' => 'Oral Medications',
                'description' => 'أقراص وكبسولات وشراب',
                'parent_code' => 'MED',
                'is_controlled' => false,
            ],
            [
                'code' => 'MED_INJ',
                'name_ar' => 'حقن',
                'name_en' => 'Injectables',
                'description' => 'أدوية الحقن الوريدي والعضلي',
                'parent_code' => 'MED',
                'is_controlled' => false,
            ],
            [
                'code' => 'MED_TOP',
                'name_ar' => 'أدوية موضعية',
                'name_en' => 'Topical Medications',
                'description' => 'كريمات ومراهم وقطرات',
                'parent_code' => 'MED',
                'is_controlled' => false,
            ],
            [
                'code' => 'MED_CTRL',
                'name_ar' => 'أدوية مراقبة',
                'name_en' => 'Controlled Substances',
                'description' => 'المخدرات والمؤثرات العقلية',
                'parent_code' => 'MED',
                'is_controlled' => true,
            ],

            // المستهلكات الطبية
            [
                'code' => 'CONS',
                'name_ar' => 'المستهلكات الطبية',
                'name_en' => 'Medical Consumables',
                'description' => 'المواد المستهلكة في الرعاية الصحية',
                'parent_code' => null,
                'is_controlled' => false,
            ],
            [
                'code' => 'CONS_SYR',
                'name_ar' => 'المحاقن والإبر',
                'name_en' => 'Syringes & Needles',
                'description' => 'جميع أحجام المحاقن والإبر',
                'parent_code' => 'CONS',
                'is_controlled' => false,
            ],
            [
                'code' => 'CONS_DRESS',
                'name_ar' => 'الضمادات',
                'name_en' => 'Dressings',
                'description' => 'شاش وضمادات وأربطة',
                'parent_code' => 'CONS',
                'is_controlled' => false,
            ],
            [
                'code' => 'CONS_GLOVE',
                'name_ar' => 'القفازات',
                'name_en' => 'Gloves',
                'description' => 'قفازات طبية متنوعة',
                'parent_code' => 'CONS',
                'is_controlled' => false,
            ],
            [
                'code' => 'CONS_IV',
                'name_ar' => 'مستلزمات الوريد',
                'name_en' => 'IV Supplies',
                'description' => 'كانيولا ومحاليل وأنابيب',
                'parent_code' => 'CONS',
                'is_controlled' => false,
            ],

            // معدات التشخيص
            [
                'code' => 'DIAG',
                'name_ar' => 'معدات التشخيص',
                'name_en' => 'Diagnostic Equipment',
                'description' => 'أجهزة ومعدات الفحص',
                'parent_code' => null,
                'is_controlled' => false,
            ],
            [
                'code' => 'DIAG_LAB',
                'name_ar' => 'مستلزمات المختبر',
                'name_en' => 'Laboratory Supplies',
                'description' => 'أنابيب وكواشف ومستهلكات المختبر',
                'parent_code' => 'DIAG',
                'is_controlled' => false,
            ],

            // معدات الأسنان
            [
                'code' => 'DENT',
                'name_ar' => 'مستلزمات الأسنان',
                'name_en' => 'Dental Supplies',
                'description' => 'أدوات ومواد طب الأسنان',
                'parent_code' => null,
                'is_controlled' => false,
            ],
            [
                'code' => 'DENT_FILL',
                'name_ar' => 'مواد الحشو',
                'name_en' => 'Filling Materials',
                'description' => 'حشوات وأسمنت الأسنان',
                'parent_code' => 'DENT',
                'is_controlled' => false,
            ],

            // الطوارئ
            [
                'code' => 'EMERG',
                'name_ar' => 'مستلزمات الطوارئ',
                'name_en' => 'Emergency Supplies',
                'description' => 'مستلزمات حالات الطوارئ',
                'parent_code' => null,
                'is_controlled' => false,
            ],
        ];

        $now = now();
        $catIds = [];

        // المرحلة الأولى: إنشاء المعرفات
        foreach ($categories as &$cat) {
            $cat['id'] = Str::uuid()->toString();
            $catIds[$cat['code']] = $cat['id'];
        }

        // المرحلة الثانية: إدراج البيانات
        $insertData = [];
        foreach ($categories as $cat) {
            $insertData[] = [
                'id' => $cat['id'],
                'code' => $cat['code'],
                'name_ar' => $cat['name_ar'],
                'name_en' => $cat['name_en'],
                'description' => $cat['description'],
                'parent_id' => $cat['parent_code'] ? ($catIds[$cat['parent_code']] ?? null) : null,
                'is_controlled' => $cat['is_controlled'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('item_categories')->insertOrIgnore($insertData);

        $this->command->info('   ✓ تم إنشاء ' . count($categories) . ' فئة');

        // عرض الفئات الرئيسية
        $mainCategories = collect($categories)->filter(fn($c) => $c['parent_code'] === null);
        $this->command->table(
            ['الكود', 'الفئة', 'مراقبة'],
            $mainCategories->map(fn($c) => [
                $c['code'],
                $c['name_ar'],
                $c['is_controlled'] ? '✓' : '-',
            ])->toArray()
        );
    }

    /**
     * تعبئة المستودعات
     */
    protected function seedWarehouses(): void
    {
        $this->command->info('2. إنشاء المستودعات...');

        // جلب معرفات الأقسام
        $departments = DB::table('departments')->pluck('id', 'code');

        $warehouses = [
            // المستودع الرئيسي
            [
                'code' => 'MAIN',
                'name_ar' => 'المستودع الرئيسي',
                'name_en' => 'Main Warehouse',
                'type' => 'main',
                'department_code' => null,
                'location' => 'الدور الأرضي - جناح الخدمات',
                'is_temperature_controlled' => true,
                'min_temperature' => 15,
                'max_temperature' => 25,
            ],

            // الصيدلية
            [
                'code' => 'PHARM_MAIN',
                'name_ar' => 'صيدلية المرضى',
                'name_en' => 'Patient Pharmacy',
                'type' => 'pharmacy',
                'department_code' => 'PHARM',
                'location' => 'الدور الأرضي - البهو الرئيسي',
                'is_temperature_controlled' => true,
                'min_temperature' => 15,
                'max_temperature' => 25,
            ],
            [
                'code' => 'PHARM_CTRL',
                'name_ar' => 'خزينة المراقبة',
                'name_en' => 'Controlled Substances Safe',
                'type' => 'controlled',
                'department_code' => 'PHARM',
                'location' => 'الصيدلية - الغرفة الآمنة',
                'is_temperature_controlled' => true,
                'min_temperature' => 15,
                'max_temperature' => 25,
            ],
            [
                'code' => 'PHARM_COLD',
                'name_ar' => 'الثلاجة الرئيسية',
                'name_en' => 'Main Cold Storage',
                'type' => 'cold_storage',
                'department_code' => 'PHARM',
                'location' => 'الصيدلية',
                'is_temperature_controlled' => true,
                'min_temperature' => 2,
                'max_temperature' => 8,
            ],

            // الطوارئ
            [
                'code' => 'ER_CRASH',
                'name_ar' => 'عربة الطوارئ',
                'name_en' => 'Crash Cart',
                'type' => 'crash_cart',
                'department_code' => null,
                'location' => 'قسم الطوارئ',
                'is_temperature_controlled' => false,
                'min_temperature' => null,
                'max_temperature' => null,
            ],

            // المختبر
            [
                'code' => 'LAB_STORE',
                'name_ar' => 'مستودع المختبر',
                'name_en' => 'Laboratory Storage',
                'type' => 'department',
                'department_code' => 'LAB',
                'location' => 'المختبر - غرفة التخزين',
                'is_temperature_controlled' => true,
                'min_temperature' => 15,
                'max_temperature' => 25,
            ],
            [
                'code' => 'LAB_COLD',
                'name_ar' => 'ثلاجة المختبر',
                'name_en' => 'Laboratory Refrigerator',
                'type' => 'cold_storage',
                'department_code' => 'LAB',
                'location' => 'المختبر',
                'is_temperature_controlled' => true,
                'min_temperature' => 2,
                'max_temperature' => 8,
            ],

            // الأسنان
            [
                'code' => 'DENT_STORE',
                'name_ar' => 'مستودع الأسنان',
                'name_en' => 'Dental Storage',
                'type' => 'department',
                'department_code' => 'MED_DENT',
                'location' => 'قسم الأسنان',
                'is_temperature_controlled' => false,
                'min_temperature' => null,
                'max_temperature' => null,
            ],

            // التمريض
            [
                'code' => 'NUR_STORE',
                'name_ar' => 'مستودع التمريض',
                'name_en' => 'Nursing Storage',
                'type' => 'department',
                'department_code' => 'NUR',
                'location' => 'محطة التمريض الرئيسية',
                'is_temperature_controlled' => false,
                'min_temperature' => null,
                'max_temperature' => null,
            ],
        ];

        $now = now();
        $insertData = [];

        foreach ($warehouses as $wh) {
            $insertData[] = [
                'id' => Str::uuid(),
                'code' => $wh['code'],
                'name_ar' => $wh['name_ar'],
                'name_en' => $wh['name_en'],
                'type' => $wh['type'],
                'department_id' => $wh['department_code'] ? ($departments[$wh['department_code']] ?? null) : null,
                'location' => $wh['location'],
                'is_temperature_controlled' => $wh['is_temperature_controlled'],
                'min_temperature' => $wh['min_temperature'],
                'max_temperature' => $wh['max_temperature'],
                'manager_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('warehouses')->insertOrIgnore($insertData);

        $this->command->info('   ✓ تم إنشاء ' . count($warehouses) . ' مستودع');

        // عرض المستودعات
        $this->command->table(
            ['الكود', 'المستودع', 'النوع', 'درجة الحرارة'],
            collect($warehouses)->map(fn($w) => [
                $w['code'],
                $w['name_ar'],
                $w['type'],
                $w['is_temperature_controlled']
                    ? $w['min_temperature'] . '°-' . $w['max_temperature'] . '°'
                    : '-',
            ])->toArray()
        );
    }
}
