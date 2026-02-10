<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HRSeeder extends Seeder
{
    /**
     * تعبئة بيانات وحدة الموارد البشرية
     * Seed the HR module data.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  تعبئة بيانات وحدة الموارد البشرية');
        $this->command->info('  HR Module Seeding');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');

        // 1. الأقسام
        $this->seedDepartments();

        // 2. المناصب الوظيفية
        $this->seedPositions();

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✓ اكتملت تعبئة وحدة الموارد البشرية');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
    }

    /**
     * تعبئة الأقسام
     * الهيكل التنظيمي النموذجي لمنشأة طبية
     */
    protected function seedDepartments(): void
    {
        $this->command->info('1. إنشاء الأقسام...');

        $departments = [
            // الإدارة العليا
            [
                'code' => 'EXEC',
                'name_ar' => 'الإدارة العليا',
                'name_en' => 'Executive Management',
                'description_ar' => 'المدير العام والإدارة التنفيذية',
                'parent_code' => null,
                'cost_center_code' => '1000',
                'sort_order' => 1,
            ],

            // الإدارة الطبية
            [
                'code' => 'MED',
                'name_ar' => 'الإدارة الطبية',
                'name_en' => 'Medical Department',
                'description_ar' => 'الخدمات الطبية والعيادات',
                'parent_code' => null,
                'cost_center_code' => '2000',
                'sort_order' => 2,
            ],
            [
                'code' => 'MED_GP',
                'name_ar' => 'الطب العام',
                'name_en' => 'General Practice',
                'description_ar' => 'عيادات الطب العام',
                'parent_code' => 'MED',
                'cost_center_code' => '2100',
                'sort_order' => 1,
            ],
            [
                'code' => 'MED_DENT',
                'name_ar' => 'طب الأسنان',
                'name_en' => 'Dental',
                'description_ar' => 'عيادات الأسنان',
                'parent_code' => 'MED',
                'cost_center_code' => '2200',
                'sort_order' => 2,
            ],
            [
                'code' => 'MED_PEDS',
                'name_ar' => 'طب الأطفال',
                'name_en' => 'Pediatrics',
                'description_ar' => 'عيادات الأطفال',
                'parent_code' => 'MED',
                'cost_center_code' => '2300',
                'sort_order' => 3,
            ],
            [
                'code' => 'MED_INT',
                'name_ar' => 'الباطنية',
                'name_en' => 'Internal Medicine',
                'description_ar' => 'عيادات الباطنية',
                'parent_code' => 'MED',
                'cost_center_code' => '2400',
                'sort_order' => 4,
            ],
            [
                'code' => 'MED_OB',
                'name_ar' => 'النساء والولادة',
                'name_en' => 'Obstetrics & Gynecology',
                'description_ar' => 'عيادات النساء والولادة',
                'parent_code' => 'MED',
                'cost_center_code' => '2500',
                'sort_order' => 5,
            ],

            // التمريض
            [
                'code' => 'NUR',
                'name_ar' => 'التمريض',
                'name_en' => 'Nursing',
                'description_ar' => 'خدمات التمريض',
                'parent_code' => null,
                'cost_center_code' => '3000',
                'sort_order' => 3,
            ],

            // المختبر والأشعة
            [
                'code' => 'LAB',
                'name_ar' => 'المختبر',
                'name_en' => 'Laboratory',
                'description_ar' => 'خدمات المختبر والتحاليل',
                'parent_code' => null,
                'cost_center_code' => '4000',
                'sort_order' => 4,
            ],
            [
                'code' => 'RAD',
                'name_ar' => 'الأشعة',
                'name_en' => 'Radiology',
                'description_ar' => 'خدمات الأشعة والتصوير',
                'parent_code' => null,
                'cost_center_code' => '4500',
                'sort_order' => 5,
            ],

            // الصيدلية
            [
                'code' => 'PHARM',
                'name_ar' => 'الصيدلية',
                'name_en' => 'Pharmacy',
                'description_ar' => 'خدمات الصيدلة',
                'parent_code' => null,
                'cost_center_code' => '5000',
                'sort_order' => 6,
            ],

            // الإدارة المالية
            [
                'code' => 'FIN',
                'name_ar' => 'الشؤون المالية',
                'name_en' => 'Finance',
                'description_ar' => 'المحاسبة والمالية',
                'parent_code' => null,
                'cost_center_code' => '6000',
                'sort_order' => 7,
            ],

            // الموارد البشرية
            [
                'code' => 'HR',
                'name_ar' => 'الموارد البشرية',
                'name_en' => 'Human Resources',
                'description_ar' => 'شؤون الموظفين',
                'parent_code' => null,
                'cost_center_code' => '7000',
                'sort_order' => 8,
            ],

            // تقنية المعلومات
            [
                'code' => 'IT',
                'name_ar' => 'تقنية المعلومات',
                'name_en' => 'Information Technology',
                'description_ar' => 'الدعم التقني والأنظمة',
                'parent_code' => null,
                'cost_center_code' => '8000',
                'sort_order' => 9,
            ],

            // الخدمات المساندة
            [
                'code' => 'SUP',
                'name_ar' => 'الخدمات المساندة',
                'name_en' => 'Support Services',
                'description_ar' => 'الخدمات العامة والصيانة',
                'parent_code' => null,
                'cost_center_code' => '9000',
                'sort_order' => 10,
            ],
            [
                'code' => 'SUP_REC',
                'name_ar' => 'الاستقبال',
                'name_en' => 'Reception',
                'description_ar' => 'خدمات الاستقبال',
                'parent_code' => 'SUP',
                'cost_center_code' => '9100',
                'sort_order' => 1,
            ],
            [
                'code' => 'SUP_SEC',
                'name_ar' => 'الأمن',
                'name_en' => 'Security',
                'description_ar' => 'خدمات الأمن والسلامة',
                'parent_code' => 'SUP',
                'cost_center_code' => '9200',
                'sort_order' => 2,
            ],
        ];

        $now = now();
        $deptIds = [];

        // المرحلة الأولى: إنشاء جميع الأقسام بدون parent_id
        foreach ($departments as &$dept) {
            $dept['id'] = Str::uuid()->toString();
            $deptIds[$dept['code']] = $dept['id'];
            $dept['is_active'] = true;
            $dept['created_at'] = $now;
            $dept['updated_at'] = $now;
        }

        // المرحلة الثانية: تحديد parent_id
        $insertData = [];
        foreach ($departments as $dept) {
            $insertData[] = [
                'id' => $dept['id'],
                'code' => $dept['code'],
                'name_ar' => $dept['name_ar'],
                'name_en' => $dept['name_en'],
                'description_ar' => $dept['description_ar'],
                'parent_id' => $dept['parent_code'] ? ($deptIds[$dept['parent_code']] ?? null) : null,
                'manager_id' => null,
                'cost_center_code' => $dept['cost_center_code'],
                'is_active' => true,
                'sort_order' => $dept['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('departments')->insertOrIgnore($insertData);

        $this->command->info('   ✓ تم إنشاء ' . count($departments) . ' قسم');

        // عرض الهيكل التنظيمي
        $this->command->table(
            ['الكود', 'القسم', 'القسم الرئيسي', 'مركز التكلفة'],
            collect($departments)->map(fn($d) => [
                $d['code'],
                $d['name_ar'],
                $d['parent_code'] ?? '-',
                $d['cost_center_code'],
            ])->toArray()
        );
    }

    /**
     * تعبئة المناصب الوظيفية
     */
    protected function seedPositions(): void
    {
        $this->command->info('2. إنشاء المناصب الوظيفية...');

        // جلب معرفات الأقسام
        $departments = DB::table('departments')->pluck('id', 'code');

        $positions = [
            // الإدارة العليا
            [
                'code' => 'GM',
                'name_ar' => 'المدير العام',
                'name_en' => 'General Manager',
                'department_code' => 'EXEC',
                'level' => 'executive',
                'min_salary' => 35000,
                'max_salary' => 60000,
                'is_medical' => false,
            ],
            [
                'code' => 'ADM_MGR',
                'name_ar' => 'المدير الإداري',
                'name_en' => 'Administrative Manager',
                'department_code' => 'EXEC',
                'level' => 'executive',
                'min_salary' => 25000,
                'max_salary' => 40000,
                'is_medical' => false,
            ],
            [
                'code' => 'MED_DIR',
                'name_ar' => 'المدير الطبي',
                'name_en' => 'Medical Director',
                'department_code' => 'MED',
                'level' => 'executive',
                'min_salary' => 40000,
                'max_salary' => 70000,
                'is_medical' => true,
                'requires_license' => true,
            ],

            // الأطباء
            [
                'code' => 'CONS',
                'name_ar' => 'استشاري',
                'name_en' => 'Consultant',
                'department_code' => 'MED',
                'level' => 'senior',
                'min_salary' => 30000,
                'max_salary' => 55000,
                'is_medical' => true,
                'requires_license' => true,
            ],
            [
                'code' => 'SPEC',
                'name_ar' => 'أخصائي',
                'name_en' => 'Specialist',
                'department_code' => 'MED',
                'level' => 'senior',
                'min_salary' => 20000,
                'max_salary' => 35000,
                'is_medical' => true,
                'requires_license' => true,
            ],
            [
                'code' => 'GP',
                'name_ar' => 'طبيب عام',
                'name_en' => 'General Practitioner',
                'department_code' => 'MED_GP',
                'level' => 'junior',
                'min_salary' => 15000,
                'max_salary' => 25000,
                'is_medical' => true,
                'requires_license' => true,
            ],
            [
                'code' => 'DENT',
                'name_ar' => 'طبيب أسنان',
                'name_en' => 'Dentist',
                'department_code' => 'MED_DENT',
                'level' => 'senior',
                'min_salary' => 18000,
                'max_salary' => 35000,
                'is_medical' => true,
                'requires_license' => true,
            ],

            // التمريض
            [
                'code' => 'NUR_MGR',
                'name_ar' => 'مديرة التمريض',
                'name_en' => 'Nursing Manager',
                'department_code' => 'NUR',
                'level' => 'manager',
                'min_salary' => 12000,
                'max_salary' => 18000,
                'is_medical' => true,
                'requires_license' => true,
            ],
            [
                'code' => 'NUR_SUP',
                'name_ar' => 'مشرفة تمريض',
                'name_en' => 'Nursing Supervisor',
                'department_code' => 'NUR',
                'level' => 'supervisor',
                'min_salary' => 8000,
                'max_salary' => 12000,
                'is_medical' => true,
                'requires_license' => true,
            ],
            [
                'code' => 'RN',
                'name_ar' => 'ممرض/ة',
                'name_en' => 'Registered Nurse',
                'department_code' => 'NUR',
                'level' => 'junior',
                'min_salary' => 5000,
                'max_salary' => 9000,
                'is_medical' => true,
                'requires_license' => true,
            ],

            // المختبر
            [
                'code' => 'LAB_MGR',
                'name_ar' => 'مدير المختبر',
                'name_en' => 'Laboratory Manager',
                'department_code' => 'LAB',
                'level' => 'manager',
                'min_salary' => 15000,
                'max_salary' => 25000,
                'is_medical' => true,
                'requires_license' => true,
            ],
            [
                'code' => 'LAB_TECH',
                'name_ar' => 'فني مختبر',
                'name_en' => 'Lab Technician',
                'department_code' => 'LAB',
                'level' => 'junior',
                'min_salary' => 5000,
                'max_salary' => 9000,
                'is_medical' => true,
                'requires_license' => true,
            ],

            // الأشعة
            [
                'code' => 'RAD_TECH',
                'name_ar' => 'فني أشعة',
                'name_en' => 'Radiology Technician',
                'department_code' => 'RAD',
                'level' => 'junior',
                'min_salary' => 5000,
                'max_salary' => 10000,
                'is_medical' => true,
                'requires_license' => true,
            ],

            // الصيدلية
            [
                'code' => 'PHARM_MGR',
                'name_ar' => 'مدير الصيدلية',
                'name_en' => 'Pharmacy Manager',
                'department_code' => 'PHARM',
                'level' => 'manager',
                'min_salary' => 12000,
                'max_salary' => 20000,
                'is_medical' => true,
                'requires_license' => true,
            ],
            [
                'code' => 'PHARMACIST',
                'name_ar' => 'صيدلي',
                'name_en' => 'Pharmacist',
                'department_code' => 'PHARM',
                'level' => 'junior',
                'min_salary' => 8000,
                'max_salary' => 15000,
                'is_medical' => true,
                'requires_license' => true,
            ],

            // الشؤون المالية
            [
                'code' => 'FIN_MGR',
                'name_ar' => 'المدير المالي',
                'name_en' => 'Finance Manager',
                'department_code' => 'FIN',
                'level' => 'manager',
                'min_salary' => 15000,
                'max_salary' => 25000,
                'is_medical' => false,
            ],
            [
                'code' => 'ACCOUNTANT',
                'name_ar' => 'محاسب',
                'name_en' => 'Accountant',
                'department_code' => 'FIN',
                'level' => 'junior',
                'min_salary' => 5000,
                'max_salary' => 10000,
                'is_medical' => false,
            ],

            // الموارد البشرية
            [
                'code' => 'HR_MGR',
                'name_ar' => 'مدير الموارد البشرية',
                'name_en' => 'HR Manager',
                'department_code' => 'HR',
                'level' => 'manager',
                'min_salary' => 12000,
                'max_salary' => 20000,
                'is_medical' => false,
            ],
            [
                'code' => 'HR_SPEC',
                'name_ar' => 'أخصائي موارد بشرية',
                'name_en' => 'HR Specialist',
                'department_code' => 'HR',
                'level' => 'junior',
                'min_salary' => 5000,
                'max_salary' => 10000,
                'is_medical' => false,
            ],

            // تقنية المعلومات
            [
                'code' => 'IT_MGR',
                'name_ar' => 'مدير تقنية المعلومات',
                'name_en' => 'IT Manager',
                'department_code' => 'IT',
                'level' => 'manager',
                'min_salary' => 12000,
                'max_salary' => 22000,
                'is_medical' => false,
            ],
            [
                'code' => 'IT_SUPPORT',
                'name_ar' => 'فني دعم تقني',
                'name_en' => 'IT Support',
                'department_code' => 'IT',
                'level' => 'entry',
                'min_salary' => 4000,
                'max_salary' => 8000,
                'is_medical' => false,
            ],

            // الاستقبال
            [
                'code' => 'RECEP',
                'name_ar' => 'موظف استقبال',
                'name_en' => 'Receptionist',
                'department_code' => 'SUP_REC',
                'level' => 'entry',
                'min_salary' => 3500,
                'max_salary' => 6000,
                'is_medical' => false,
            ],

            // الأمن
            [
                'code' => 'SEC_GUARD',
                'name_ar' => 'حارس أمن',
                'name_en' => 'Security Guard',
                'department_code' => 'SUP_SEC',
                'level' => 'entry',
                'min_salary' => 3000,
                'max_salary' => 5000,
                'is_medical' => false,
            ],
        ];

        $now = now();
        $insertData = [];

        foreach ($positions as $pos) {
            $insertData[] = [
                'id' => Str::uuid(),
                'code' => $pos['code'],
                'name_ar' => $pos['name_ar'],
                'name_en' => $pos['name_en'],
                'description_ar' => null,
                'department_id' => $departments[$pos['department_code']] ?? null,
                'level' => $pos['level'],
                'min_salary' => $pos['min_salary'],
                'max_salary' => $pos['max_salary'],
                'is_medical' => $pos['is_medical'],
                'requires_license' => $pos['requires_license'] ?? false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('positions')->insertOrIgnore($insertData);

        $this->command->info('   ✓ تم إنشاء ' . count($positions) . ' منصب وظيفي');

        // عرض ملخص المناصب
        $this->command->table(
            ['الكود', 'المنصب', 'المستوى', 'الحد الأدنى', 'الحد الأقصى', 'طبي'],
            collect($positions)->take(10)->map(fn($p) => [
                $p['code'],
                Str::limit($p['name_ar'], 20),
                $p['level'],
                number_format($p['min_salary']) . ' ر.س',
                number_format($p['max_salary']) . ' ر.س',
                $p['is_medical'] ? '✓' : '-',
            ])->toArray()
        );

        $this->command->info('   (عرض أول 10 مناصب من ' . count($positions) . ')');
    }
}
