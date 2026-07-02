<?php

namespace Database\Seeders;

use App\Models\ViolationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * جدول المخالفات والجزاءات حسب نظام العمل السعودي
 * مرجع: لائحة تنظيم العمل - الملحق رقم (1) جدول المخالفات والجزاءات
 * المواد: 66، 67، 71، 80 من نظام العمل السعودي
 */
class ViolationTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('⚖️  إنشاء جدول المخالفات حسب نظام العمل السعودي...');

        $categories = $this->getViolationCategories();

        $sortOrder = 1;
        foreach ($categories as $category) {
            foreach ($category['violations'] as $violation) {
                ViolationType::firstOrCreate(
                    ['code' => $violation['code']],
                    array_merge($violation, [
                        'id' => Str::uuid(),
                        'category' => $category['key'],
                        'category_ar' => $category['name_ar'],
                        'is_active' => true,
                        'sort_order' => $sortOrder++,
                    ])
                );
            }
        }

        $this->command->info("   ✅ تم إنشاء {$sortOrder} نوع مخالفة");
    }

    private function getViolationCategories(): array
    {
        return [
            // ═══════════════════════════════════════════════════════
            // الفئة الأولى: مخالفات الحضور والانصراف والدوام
            // ═══════════════════════════════════════════════════════
            [
                'key' => 'attendance',
                'name_ar' => 'مخالفات الحضور والانصراف',
                'violations' => [
                    [
                        'code' => 'ATT-001',
                        'name' => 'Late arrival without excuse',
                        'name_ar' => 'التأخر عن مواعيد العمل بدون عذر مقبول',
                        'description' => 'Employee arrives late to work without an acceptable excuse',
                        'description_ar' => 'حضور الموظف بعد الموعد المحدد لبدء العمل دون عذر مقبول',
                        'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
                        'severity' => 'minor',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'verbal_warning', 'penalty_ar' => 'إنذار شفهي', 'details_ar' => 'تنبيه شفهي مع التوثيق'],
                            ['occurrence' => 2, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي أول'],
                            ['occurrence' => 3, 'penalty' => 'deduction_1_day', 'penalty_ar' => 'خصم يوم واحد', 'deduction_days' => 1, 'details_ar' => 'خصم أجر يوم واحد'],
                            ['occurrence' => 4, 'penalty' => 'deduction_3_days', 'penalty_ar' => 'خصم ثلاثة أيام', 'deduction_days' => 3, 'details_ar' => 'خصم أجر ثلاثة أيام'],
                            ['occurrence' => 5, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                        ],
                    ],
                    [
                        'code' => 'ATT-002',
                        'name' => 'Leaving work early without permission',
                        'name_ar' => 'الانصراف قبل الموعد المحدد دون إذن',
                        'description' => 'Employee leaves work before the scheduled end time without prior permission',
                        'description_ar' => 'مغادرة الموظف لمقر العمل قبل انتهاء ساعات الدوام الرسمي دون إذن مسبق',
                        'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
                        'severity' => 'minor',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'verbal_warning', 'penalty_ar' => 'إنذار شفهي', 'details_ar' => 'تنبيه شفهي'],
                            ['occurrence' => 2, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                            ['occurrence' => 3, 'penalty' => 'deduction_1_day', 'penalty_ar' => 'خصم يوم واحد', 'deduction_days' => 1, 'details_ar' => 'خصم أجر يوم واحد'],
                            ['occurrence' => 4, 'penalty' => 'deduction_3_days', 'penalty_ar' => 'خصم ثلاثة أيام', 'deduction_days' => 3, 'details_ar' => 'خصم أجر ثلاثة أيام'],
                        ],
                    ],
                    [
                        'code' => 'ATT-003',
                        'name' => 'Absence without excuse (1-2 days)',
                        'name_ar' => 'الغياب بدون عذر مقبول (1-2 يوم)',
                        'description' => 'Absence from work for 1-2 days without acceptable excuse',
                        'description_ar' => 'تغيب الموظف عن العمل لمدة يوم أو يومين بدون عذر مقبول',
                        'labor_law_article' => 'المادة 80 فقرة 7 - نظام العمل',
                        'severity' => 'moderate',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي مع خصم أيام الغياب'],
                            ['occurrence' => 2, 'penalty' => 'deduction_2_days', 'penalty_ar' => 'خصم يومين إضافيين', 'deduction_days' => 2, 'details_ar' => 'خصم أجر يومين بالإضافة لأيام الغياب'],
                            ['occurrence' => 3, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام', 'deduction_days' => 5, 'details_ar' => 'خصم خمسة أيام مع إنذار نهائي'],
                            ['occurrence' => 4, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ عقد العمل بدون مكافأة حسب المادة 80'],
                        ],
                    ],
                    [
                        'code' => 'ATT-004',
                        'name' => 'Consecutive absence (20+ days) or intermittent (30+ days/year)',
                        'name_ar' => 'الغياب 20 يوماً متصلة أو 30 يوماً متفرقة خلال السنة',
                        'description' => 'Absence for 20+ consecutive days or 30+ intermittent days within a contract year without legitimate reason',
                        'description_ar' => 'تغيب الموظف عن العمل لأكثر من عشرين يوماً متصلة أو ثلاثين يوماً متفرقة خلال السنة التعاقدية دون سبب مشروع',
                        'labor_law_article' => 'المادة 80 فقرة 7 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد بدون مكافأة', 'details_ar' => 'يحق لصاحب العمل فسخ العقد دون مكافأة أو إشعار حسب المادة 80 فقرة 7 بشرط الإنذار الكتابي المسبق'],
                        ],
                    ],
                    [
                        'code' => 'ATT-005',
                        'name' => 'Failure to record attendance (buddy punching)',
                        'name_ar' => 'عدم تسجيل الحضور أو تسجيل حضور الغير',
                        'description' => 'Failing to personally record attendance or recording attendance for another employee',
                        'description_ar' => 'عدم تسجيل الحضور والانصراف في النظام أو تسجيل حضور أو انصراف موظف آخر',
                        'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
                        'severity' => 'moderate',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي أول'],
                            ['occurrence' => 2, 'penalty' => 'deduction_3_days', 'penalty_ar' => 'خصم ثلاثة أيام', 'deduction_days' => 3, 'details_ar' => 'خصم أجر ثلاثة أيام'],
                            ['occurrence' => 3, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام مع إنذار نهائي', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            // الفئة الثانية: مخالفات سلوك العمل والانضباط
            // ═══════════════════════════════════════════════════════
            [
                'key' => 'conduct',
                'name_ar' => 'مخالفات السلوك والانضباط',
                'violations' => [
                    [
                        'code' => 'CON-001',
                        'name' => 'Negligence in performing duties',
                        'name_ar' => 'الإهمال في أداء واجبات العمل',
                        'description' => 'Neglecting to perform assigned work duties properly',
                        'description_ar' => 'عدم قيام الموظف بأداء مهام عمله المكلف بها بالعناية المطلوبة',
                        'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
                        'severity' => 'moderate',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'verbal_warning', 'penalty_ar' => 'إنذار شفهي', 'details_ar' => 'تنبيه شفهي مع التوثيق'],
                            ['occurrence' => 2, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                            ['occurrence' => 3, 'penalty' => 'deduction_2_days', 'penalty_ar' => 'خصم يومين', 'deduction_days' => 2, 'details_ar' => 'خصم أجر يومين'],
                            ['occurrence' => 4, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام مع إنذار نهائي', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                        ],
                    ],
                    [
                        'code' => 'CON-002',
                        'name' => 'Refusing to carry out work orders',
                        'name_ar' => 'رفض تنفيذ الأوامر المتعلقة بالعمل',
                        'description' => 'Deliberately refusing to carry out legitimate work-related orders from a superior',
                        'description_ar' => 'رفض الموظف تنفيذ الأوامر والتعليمات المشروعة المتعلقة بالعمل الصادرة من رئيسه المباشر',
                        'labor_law_article' => 'المادة 80 فقرة 2 - نظام العمل',
                        'severity' => 'major',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                            ['occurrence' => 2, 'penalty' => 'deduction_3_days', 'penalty_ar' => 'خصم ثلاثة أيام', 'deduction_days' => 3, 'details_ar' => 'خصم أجر ثلاثة أيام'],
                            ['occurrence' => 3, 'penalty' => 'deduction_5_days_suspension', 'penalty_ar' => 'خصم خمسة أيام مع إيقاف', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إيقاف عن العمل'],
                            ['occurrence' => 4, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ العقد بدون مكافأة حسب المادة 80'],
                        ],
                    ],
                    [
                        'code' => 'CON-003',
                        'name' => 'Verbal assault on colleague or supervisor',
                        'name_ar' => 'الاعتداء اللفظي على زميل أو رئيس',
                        'description' => 'Verbally assaulting, insulting, or using foul language against a colleague or supervisor',
                        'description_ar' => 'الإساءة اللفظية أو السب أو الشتم أو استخدام ألفاظ بذيئة تجاه زميل أو رئيس في العمل',
                        'labor_law_article' => 'المادة 80 فقرة 6 - نظام العمل',
                        'severity' => 'major',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي مع خصم', 'deduction_days' => 2, 'details_ar' => 'إنذار كتابي مع خصم يومين'],
                            ['occurrence' => 2, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام'],
                            ['occurrence' => 3, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ العقد بدون مكافأة حسب المادة 80 فقرة 6'],
                        ],
                    ],
                    [
                        'code' => 'CON-004',
                        'name' => 'Physical assault at workplace',
                        'name_ar' => 'الاعتداء الجسدي في مكان العمل',
                        'description' => 'Committing physical assault against a colleague, supervisor, or any person in the workplace',
                        'description_ar' => 'الاعتداء بالضرب أو استخدام القوة الجسدية ضد زميل أو رئيس أو أي شخص في مكان العمل',
                        'labor_law_article' => 'المادة 80 فقرة 6 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد فوراً', 'details_ar' => 'فسخ العقد فوراً بدون مكافأة أو تعويض مع إبلاغ الجهات المختصة حسب المادة 80 فقرة 6'],
                        ],
                    ],
                    [
                        'code' => 'CON-005',
                        'name' => 'Using work equipment for personal purposes',
                        'name_ar' => 'استخدام معدات العمل لأغراض شخصية',
                        'description' => 'Using company equipment, tools, or facilities for personal purposes without authorization',
                        'description_ar' => 'استخدام أدوات ومعدات ومرافق الشركة لأغراض شخصية بدون إذن',
                        'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
                        'severity' => 'minor',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'verbal_warning', 'penalty_ar' => 'إنذار شفهي', 'details_ar' => 'تنبيه شفهي'],
                            ['occurrence' => 2, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                            ['occurrence' => 3, 'penalty' => 'deduction_1_day', 'penalty_ar' => 'خصم يوم', 'deduction_days' => 1, 'details_ar' => 'خصم أجر يوم واحد'],
                            ['occurrence' => 4, 'penalty' => 'deduction_3_days', 'penalty_ar' => 'خصم ثلاثة أيام', 'deduction_days' => 3, 'details_ar' => 'خصم أجر ثلاثة أيام'],
                        ],
                    ],
                    [
                        'code' => 'CON-006',
                        'name' => 'Sleeping during working hours',
                        'name_ar' => 'النوم أثناء ساعات العمل',
                        'description' => 'Sleeping during official working hours',
                        'description_ar' => 'النوم أثناء ساعات العمل الرسمية',
                        'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
                        'severity' => 'moderate',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                            ['occurrence' => 2, 'penalty' => 'deduction_2_days', 'penalty_ar' => 'خصم يومين', 'deduction_days' => 2, 'details_ar' => 'خصم أجر يومين'],
                            ['occurrence' => 3, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            // الفئة الثالثة: مخالفات السرية والأمانة
            // ═══════════════════════════════════════════════════════
            [
                'key' => 'confidentiality',
                'name_ar' => 'مخالفات السرية والأمانة',
                'violations' => [
                    [
                        'code' => 'SEC-001',
                        'name' => 'Disclosure of work secrets',
                        'name_ar' => 'إفشاء أسرار العمل',
                        'description' => 'Disclosing confidential or proprietary information to unauthorized persons',
                        'description_ar' => 'إفشاء أسرار العمل الصناعية أو التجارية أو المعلومات السرية المتعلقة بالعمل',
                        'labor_law_article' => 'المادة 80 فقرة 8 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'suspension_with_investigation', 'penalty_ar' => 'إيقاف عن العمل مع تحقيق', 'details_ar' => 'إيقاف عن العمل مع إجراء تحقيق - قد يؤدي لفسخ العقد حسب المادة 80 فقرة 8'],
                            ['occurrence' => 2, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ العقد بدون مكافأة مع حق المطالبة بالتعويض'],
                        ],
                    ],
                    [
                        'code' => 'SEC-002',
                        'name' => 'Forgery of documents or records',
                        'name_ar' => 'تزوير الوثائق أو السجلات',
                        'description' => 'Forging official documents, records, or certificates',
                        'description_ar' => 'تزوير المستندات أو الوثائق أو الشهادات الرسمية',
                        'labor_law_article' => 'المادة 80 فقرة 1 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد فوراً', 'details_ar' => 'فسخ العقد فوراً بدون مكافأة أو تعويض مع إبلاغ الجهات المختصة حسب المادة 80 فقرة 1'],
                        ],
                    ],
                    [
                        'code' => 'SEC-003',
                        'name' => 'Theft or embezzlement',
                        'name_ar' => 'السرقة أو الاختلاس',
                        'description' => 'Stealing or embezzling company assets, money, or property',
                        'description_ar' => 'سرقة أو اختلاس أموال أو ممتلكات أو أصول الشركة',
                        'labor_law_article' => 'المادة 80 فقرة 1 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد فوراً', 'details_ar' => 'فسخ العقد فوراً بدون مكافأة مع إبلاغ الجهات الأمنية واسترداد المسروقات حسب المادة 80 فقرة 1'],
                        ],
                    ],
                    [
                        'code' => 'SEC-004',
                        'name' => 'Accepting bribes or kickbacks',
                        'name_ar' => 'قبول الرشوة أو العمولات',
                        'description' => 'Accepting bribes, gifts, or kickbacks related to work duties',
                        'description_ar' => 'قبول رشاوى أو هدايا أو عمولات مرتبطة بأداء واجبات العمل',
                        'labor_law_article' => 'المادة 80 فقرة 1 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد فوراً', 'details_ar' => 'فسخ العقد فوراً بدون مكافأة مع إبلاغ الجهات المختصة'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            // الفئة الرابعة: مخالفات السلامة والصحة المهنية
            // ═══════════════════════════════════════════════════════
            [
                'key' => 'safety',
                'name_ar' => 'مخالفات السلامة والصحة المهنية',
                'violations' => [
                    [
                        'code' => 'SAF-001',
                        'name' => 'Violating safety rules and procedures',
                        'name_ar' => 'مخالفة قواعد وإجراءات السلامة',
                        'description' => 'Violating workplace safety rules, procedures, or instructions',
                        'description_ar' => 'مخالفة قواعد وإجراءات وتعليمات السلامة والصحة المهنية المعتمدة في مكان العمل',
                        'labor_law_article' => 'المادة 80 فقرة 9 - نظام العمل',
                        'severity' => 'major',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي مع خصم', 'deduction_days' => 2, 'details_ar' => 'إنذار كتابي مع خصم يومين'],
                            ['occurrence' => 2, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام', 'deduction_days' => 5, 'details_ar' => 'خصم خمسة أيام مع إنذار نهائي'],
                            ['occurrence' => 3, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ العقد حسب المادة 80 فقرة 9'],
                        ],
                    ],
                    [
                        'code' => 'SAF-002',
                        'name' => 'Not wearing personal protective equipment',
                        'name_ar' => 'عدم ارتداء معدات الوقاية الشخصية',
                        'description' => 'Failing to wear required personal protective equipment (PPE) while on duty',
                        'description_ar' => 'عدم ارتداء معدات الوقاية الشخصية المطلوبة أثناء أداء العمل',
                        'labor_law_article' => 'المادة 122 - نظام العمل',
                        'severity' => 'moderate',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'verbal_warning', 'penalty_ar' => 'إنذار شفهي', 'details_ar' => 'تنبيه شفهي'],
                            ['occurrence' => 2, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                            ['occurrence' => 3, 'penalty' => 'deduction_2_days', 'penalty_ar' => 'خصم يومين', 'deduction_days' => 2, 'details_ar' => 'خصم أجر يومين'],
                            ['occurrence' => 4, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام مع إنذار نهائي', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                        ],
                    ],
                    [
                        'code' => 'SAF-003',
                        'name' => 'Smoking in prohibited areas',
                        'name_ar' => 'التدخين في الأماكن المحظورة',
                        'description' => 'Smoking in areas where it is strictly prohibited (patient areas, labs, storage rooms)',
                        'description_ar' => 'التدخين في الأماكن الممنوع فيها التدخين كأماكن المرضى والمختبرات والمستودعات',
                        'labor_law_article' => 'المادة 66 - لائحة تنظيم العمل',
                        'severity' => 'moderate',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي'],
                            ['occurrence' => 2, 'penalty' => 'deduction_2_days', 'penalty_ar' => 'خصم يومين', 'deduction_days' => 2, 'details_ar' => 'خصم يومين'],
                            ['occurrence' => 3, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                        ],
                    ],
                    [
                        'code' => 'SAF-004',
                        'name' => 'Intentional damage to company property',
                        'name_ar' => 'إتلاف ممتلكات الشركة عمداً',
                        'description' => 'Intentionally damaging company equipment, tools, or property',
                        'description_ar' => 'إتلاف أو تدمير معدات أو أدوات أو ممتلكات الشركة بشكل متعمد',
                        'labor_law_article' => 'المادة 80 فقرة 5 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'deduction_with_compensation', 'penalty_ar' => 'خصم مع تعويض الأضرار', 'deduction_days' => 5, 'details_ar' => 'خصم خمسة أيام مع تحميل الموظف قيمة الأضرار'],
                            ['occurrence' => 2, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ العقد بدون مكافأة مع تحميل الأضرار حسب المادة 80 فقرة 5'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            // الفئة الخامسة: مخالفات مهنية طبية
            // ═══════════════════════════════════════════════════════
            [
                'key' => 'medical_professional',
                'name_ar' => 'مخالفات مهنية طبية',
                'violations' => [
                    [
                        'code' => 'MED-001',
                        'name' => 'Medical negligence',
                        'name_ar' => 'الإهمال الطبي',
                        'description' => 'Medical negligence that could affect patient safety',
                        'description_ar' => 'إهمال طبي قد يؤثر على سلامة المرضى',
                        'labor_law_article' => 'نظام مزاولة المهن الصحية - المادة 28',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'suspension_with_investigation', 'penalty_ar' => 'إيقاف مع تحقيق', 'details_ar' => 'إيقاف عن العمل وإجراء تحقيق فوري مع إبلاغ الهيئة الصحية'],
                            ['occurrence' => 2, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد مع إبلاغ الجهات', 'details_ar' => 'فسخ العقد مع إبلاغ الهيئة السعودية للتخصصات الصحية'],
                        ],
                    ],
                    [
                        'code' => 'MED-002',
                        'name' => 'Violation of patient privacy',
                        'name_ar' => 'انتهاك خصوصية المرضى',
                        'description' => 'Disclosing patient information or violating patient confidentiality',
                        'description_ar' => 'إفشاء معلومات المرضى أو انتهاك سرية بياناتهم الصحية',
                        'labor_law_article' => 'نظام مزاولة المهن الصحية - المادة 21',
                        'severity' => 'major',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning_deduction', 'penalty_ar' => 'إنذار كتابي مع خصم', 'deduction_days' => 3, 'details_ar' => 'إنذار كتابي مع خصم ثلاثة أيام'],
                            ['occurrence' => 2, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام مع إنذار نهائي', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                            ['occurrence' => 3, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ العقد مع إبلاغ الجهات المختصة'],
                        ],
                    ],
                    [
                        'code' => 'MED-003',
                        'name' => 'Failure to document medical records properly',
                        'name_ar' => 'عدم توثيق السجلات الطبية بشكل صحيح',
                        'description' => 'Failing to properly document patient medical records, treatment plans, or medications',
                        'description_ar' => 'عدم توثيق السجلات الطبية للمرضى أو خطط العلاج أو الأدوية بالشكل الصحيح',
                        'labor_law_article' => 'نظام مزاولة المهن الصحية - المادة 19',
                        'severity' => 'major',
                        'requires_investigation' => false,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning', 'penalty_ar' => 'إنذار كتابي', 'details_ar' => 'إنذار كتابي مع تدريب إضافي'],
                            ['occurrence' => 2, 'penalty' => 'deduction_2_days', 'penalty_ar' => 'خصم يومين', 'deduction_days' => 2, 'details_ar' => 'خصم أجر يومين'],
                            ['occurrence' => 3, 'penalty' => 'deduction_5_days', 'penalty_ar' => 'خصم خمسة أيام', 'deduction_days' => 5, 'details_ar' => 'خصم أجر خمسة أيام مع إنذار نهائي'],
                        ],
                    ],
                    [
                        'code' => 'MED-004',
                        'name' => 'Practicing without valid license/qualification',
                        'name_ar' => 'ممارسة المهنة بدون ترخيص أو تصنيف ساري',
                        'description' => 'Practicing medical profession without valid license or expired classification',
                        'description_ar' => 'ممارسة المهنة الصحية بدون ترخيص ساري أو تصنيف مهني منتهي',
                        'labor_law_article' => 'نظام مزاولة المهن الصحية - المادة 4',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'immediate_suspension', 'penalty_ar' => 'إيقاف فوري عن العمل', 'details_ar' => 'إيقاف فوري عن العمل حتى تجديد الترخيص أو فسخ العقد'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            // الفئة السادسة: مخالفات أخلاقية عامة
            // ═══════════════════════════════════════════════════════
            [
                'key' => 'ethics',
                'name_ar' => 'مخالفات أخلاقية عامة',
                'violations' => [
                    [
                        'code' => 'ETH-001',
                        'name' => 'Working under the influence of intoxicants',
                        'name_ar' => 'الحضور للعمل تحت تأثير مسكر أو مخدر',
                        'description' => 'Being under the influence of alcohol or drugs while at work',
                        'description_ar' => 'حضور الموظف لمقر العمل وهو تحت تأثير مادة مسكرة أو مخدرة',
                        'labor_law_article' => 'المادة 80 فقرة 4 - نظام العمل',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد فوراً', 'details_ar' => 'فسخ العقد فوراً بدون مكافأة أو تعويض مع إبلاغ الجهات الأمنية حسب المادة 80 فقرة 4'],
                        ],
                    ],
                    [
                        'code' => 'ETH-002',
                        'name' => 'Sexual harassment',
                        'name_ar' => 'التحرش الجنسي',
                        'description' => 'Any form of sexual harassment in the workplace',
                        'description_ar' => 'أي شكل من أشكال التحرش الجنسي في مكان العمل',
                        'labor_law_article' => 'المادة 80 فقرة 6 - نظام العمل + نظام مكافحة التحرش',
                        'severity' => 'critical',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد فوراً', 'details_ar' => 'فسخ العقد فوراً بدون مكافأة مع إبلاغ الجهات المختصة حسب نظام مكافحة التحرش والمادة 80 فقرة 6'],
                        ],
                    ],
                    [
                        'code' => 'ETH-003',
                        'name' => 'Conflict of interest',
                        'name_ar' => 'تضارب المصالح',
                        'description' => 'Engaging in outside work or business that conflicts with company interests',
                        'description_ar' => 'ممارسة عمل أو نشاط تجاري خارجي يتعارض مع مصالح الشركة',
                        'labor_law_article' => 'المادة 80 فقرة 3 - نظام العمل',
                        'severity' => 'major',
                        'requires_investigation' => true,
                        'penalties' => [
                            ['occurrence' => 1, 'penalty' => 'written_warning_deduction', 'penalty_ar' => 'إنذار كتابي مع خصم', 'deduction_days' => 5, 'details_ar' => 'إنذار كتابي مع خصم خمسة أيام وإلزام بإنهاء تضارب المصالح'],
                            ['occurrence' => 2, 'penalty' => 'termination', 'penalty_ar' => 'فسخ العقد', 'details_ar' => 'فسخ العقد بدون مكافأة حسب المادة 80 فقرة 3'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
