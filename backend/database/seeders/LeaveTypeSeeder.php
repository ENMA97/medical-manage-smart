<?php

namespace Database\Seeders;

use App\Models\Leave\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * تعبئة أنواع الإجازات الأساسية
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            // الإجازة السنوية
            [
                'code' => 'ANNUAL',
                'name_ar' => 'إجازة سنوية',
                'name_en' => 'Annual Leave',
                'category' => 'annual',
                'description_ar' => 'إجازة سنوية مدفوعة الأجر حسب نظام العمل السعودي (21 يوم كحد أدنى)',
                'description_en' => 'Paid annual leave according to Saudi Labor Law (minimum 21 days)',
                'default_days' => 21,
                'max_days_per_request' => 21,
                'min_days_per_request' => 1,
                'requires_attachment' => false,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => true,
                'can_be_carried_over' => true,
                'max_carry_over_days' => 10,
                'carry_over_expires_after_months' => 6,
                'advance_notice_days' => 14,
                'is_active' => true,
                'sort_order' => 1,
                'color_code' => '#4CAF50',
                'eligibility_rules' => [
                    'min_service_days' => 90,
                    'contract_types' => ['full_time', 'part_time'],
                ],
            ],

            // الإجازة المرضية
            [
                'code' => 'SICK',
                'name_ar' => 'إجازة مرضية',
                'name_en' => 'Sick Leave',
                'category' => 'sick',
                'description_ar' => 'إجازة مرضية بموجب تقرير طبي معتمد',
                'description_en' => 'Sick leave with approved medical certificate',
                'default_days' => 120,
                'max_days_per_request' => 30,
                'min_days_per_request' => 1,
                'requires_attachment' => true,
                'requires_medical_certificate' => true,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 0,
                'is_active' => true,
                'sort_order' => 2,
                'color_code' => '#F44336',
                'eligibility_rules' => [
                    'min_service_days' => 0,
                    'payment_structure' => [
                        ['days' => 30, 'percentage' => 100],
                        ['days' => 60, 'percentage' => 75],
                        ['days' => 30, 'percentage' => 0],
                    ],
                ],
            ],

            // إجازة طارئة
            [
                'code' => 'EMERGENCY',
                'name_ar' => 'إجازة طارئة',
                'name_en' => 'Emergency Leave',
                'category' => 'emergency',
                'description_ar' => 'إجازة للحالات الطارئة',
                'description_en' => 'Leave for emergency situations',
                'default_days' => 5,
                'max_days_per_request' => 3,
                'min_days_per_request' => 1,
                'requires_attachment' => false,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => true,
                'can_be_carried_over' => false,
                'advance_notice_days' => 0,
                'is_active' => true,
                'sort_order' => 3,
                'color_code' => '#FF9800',
                'eligibility_rules' => [
                    'min_service_days' => 0,
                    'max_per_year' => 5,
                ],
            ],

            // إجازة بدون راتب
            [
                'code' => 'UNPAID',
                'name_ar' => 'إجازة بدون راتب',
                'name_en' => 'Unpaid Leave',
                'category' => 'unpaid',
                'description_ar' => 'إجازة استثنائية بدون راتب',
                'description_en' => 'Exceptional leave without pay',
                'default_days' => 30,
                'max_days_per_request' => 30,
                'min_days_per_request' => 1,
                'requires_attachment' => false,
                'requires_medical_certificate' => false,
                'is_paid' => false,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 7,
                'is_active' => true,
                'sort_order' => 4,
                'color_code' => '#9E9E9E',
                'eligibility_rules' => [
                    'min_service_days' => 180,
                    'requires_gm_approval' => true,
                ],
            ],

            // إجازة أمومة
            [
                'code' => 'MATERNITY',
                'name_ar' => 'إجازة أمومة',
                'name_en' => 'Maternity Leave',
                'category' => 'maternity',
                'description_ar' => 'إجازة أمومة للموظفات (70 يوم مدفوعة الأجر)',
                'description_en' => 'Maternity leave for female employees (70 days fully paid)',
                'default_days' => 70,
                'max_days_per_request' => 70,
                'min_days_per_request' => 70,
                'requires_attachment' => true,
                'requires_medical_certificate' => true,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 30,
                'is_active' => true,
                'sort_order' => 5,
                'color_code' => '#E91E63',
                'eligibility_rules' => [
                    'gender' => 'female',
                    'min_service_days' => 0,
                ],
            ],

            // إجازة أبوة
            [
                'code' => 'PATERNITY',
                'name_ar' => 'إجازة أبوة',
                'name_en' => 'Paternity Leave',
                'category' => 'paternity',
                'description_ar' => 'إجازة أبوة للموظفين (3 أيام)',
                'description_en' => 'Paternity leave for male employees (3 days)',
                'default_days' => 3,
                'max_days_per_request' => 3,
                'min_days_per_request' => 3,
                'requires_attachment' => true,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 0,
                'is_active' => true,
                'sort_order' => 6,
                'color_code' => '#2196F3',
                'eligibility_rules' => [
                    'gender' => 'male',
                    'min_service_days' => 0,
                ],
            ],

            // إجازة حج
            [
                'code' => 'HAJJ',
                'name_ar' => 'إجازة حج',
                'name_en' => 'Hajj Leave',
                'category' => 'hajj',
                'description_ar' => 'إجازة لأداء فريضة الحج (مرة واحدة خلال الخدمة)',
                'description_en' => 'Leave for Hajj pilgrimage (once during employment)',
                'default_days' => 15,
                'max_days_per_request' => 15,
                'min_days_per_request' => 10,
                'requires_attachment' => true,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 30,
                'is_active' => true,
                'sort_order' => 7,
                'color_code' => '#795548',
                'eligibility_rules' => [
                    'min_service_years' => 2,
                    'max_lifetime_usage' => 1,
                    'religion' => 'muslim',
                ],
            ],

            // إجازة زواج
            [
                'code' => 'MARRIAGE',
                'name_ar' => 'إجازة زواج',
                'name_en' => 'Marriage Leave',
                'category' => 'marriage',
                'description_ar' => 'إجازة بمناسبة الزواج (5 أيام)',
                'description_en' => 'Marriage leave (5 days)',
                'default_days' => 5,
                'max_days_per_request' => 5,
                'min_days_per_request' => 5,
                'requires_attachment' => true,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 7,
                'is_active' => true,
                'sort_order' => 8,
                'color_code' => '#9C27B0',
                'eligibility_rules' => [
                    'min_service_days' => 0,
                    'max_lifetime_usage' => 1,
                ],
            ],

            // إجازة وفاة
            [
                'code' => 'BEREAVEMENT',
                'name_ar' => 'إجازة وفاة',
                'name_en' => 'Bereavement Leave',
                'category' => 'bereavement',
                'description_ar' => 'إجازة وفاة قريب من الدرجة الأولى (5 أيام)',
                'description_en' => 'Bereavement leave for first-degree relatives (5 days)',
                'default_days' => 5,
                'max_days_per_request' => 5,
                'min_days_per_request' => 1,
                'requires_attachment' => true,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 0,
                'is_active' => true,
                'sort_order' => 9,
                'color_code' => '#607D8B',
                'eligibility_rules' => [
                    'min_service_days' => 0,
                    'relationship_types' => ['parent', 'spouse', 'child', 'sibling'],
                ],
            ],

            // إجازة دراسية
            [
                'code' => 'STUDY',
                'name_ar' => 'إجازة دراسية',
                'name_en' => 'Study Leave',
                'category' => 'study',
                'description_ar' => 'إجازة لأداء الامتحانات أو الدراسة',
                'description_en' => 'Leave for examinations or study',
                'default_days' => 10,
                'max_days_per_request' => 10,
                'min_days_per_request' => 1,
                'requires_attachment' => true,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 14,
                'is_active' => true,
                'sort_order' => 10,
                'color_code' => '#00BCD4',
                'eligibility_rules' => [
                    'min_service_days' => 365,
                    'requires_approval' => true,
                ],
            ],

            // إجازة تعويضية
            [
                'code' => 'COMPENSATORY',
                'name_ar' => 'إجازة تعويضية',
                'name_en' => 'Compensatory Leave',
                'category' => 'compensatory',
                'description_ar' => 'إجازة تعويضية مقابل العمل الإضافي',
                'description_en' => 'Compensatory leave for overtime work',
                'default_days' => 0,
                'max_days_per_request' => 5,
                'min_days_per_request' => 1,
                'requires_attachment' => false,
                'requires_medical_certificate' => false,
                'is_paid' => true,
                'affects_annual_leave' => false,
                'can_be_carried_over' => true,
                'max_carry_over_days' => 5,
                'carry_over_expires_after_months' => 3,
                'advance_notice_days' => 3,
                'is_active' => true,
                'sort_order' => 11,
                'color_code' => '#3F51B5',
                'eligibility_rules' => [
                    'requires_overtime_approval' => true,
                ],
            ],

            // إجازة أخرى
            [
                'code' => 'OTHER',
                'name_ar' => 'إجازة أخرى',
                'name_en' => 'Other Leave',
                'category' => 'other',
                'description_ar' => 'أنواع إجازات أخرى',
                'description_en' => 'Other types of leave',
                'default_days' => 0,
                'max_days_per_request' => 30,
                'min_days_per_request' => 1,
                'requires_attachment' => true,
                'requires_medical_certificate' => false,
                'is_paid' => false,
                'affects_annual_leave' => false,
                'can_be_carried_over' => false,
                'advance_notice_days' => 7,
                'is_active' => true,
                'sort_order' => 12,
                'color_code' => '#FFEB3B',
                'eligibility_rules' => [
                    'requires_gm_approval' => true,
                ],
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('✓ تم إنشاء 12 نوع إجازة بنجاح');
    }
}
