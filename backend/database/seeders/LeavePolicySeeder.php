<?php

namespace Database\Seeders;

use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveType;
use Illuminate\Database\Seeder;

class LeavePolicySeeder extends Seeder
{
    /**
     * تعبئة سياسات الإجازات حسب أنواع العقود
     * Run the database seeds.
     */
    public function run(): void
    {
        // جلب أنواع الإجازات
        $annualLeave = LeaveType::where('code', 'ANNUAL')->first();
        $sickLeave = LeaveType::where('code', 'SICK')->first();
        $emergencyLeave = LeaveType::where('code', 'EMERGENCY')->first();
        $unpaidLeave = LeaveType::where('code', 'UNPAID')->first();

        if (!$annualLeave) {
            $this->command->warn('⚠ يجب تشغيل LeaveTypeSeeder أولاً');
            return;
        }

        $policies = [
            // سياسات الموظفين بدوام كامل
            [
                'leave_type_id' => $annualLeave->id,
                'contract_type' => 'full_time',
                'entitled_days' => 21,
                'accrual_rate' => 1.75,
                'accrual_frequency' => 'monthly',
                'waiting_period_days' => 90,
                'min_service_months' => 3,
                'max_consecutive_days' => 21,
                'requires_approval' => true,
                'can_be_encashed' => true,
                'encashment_percentage' => 100,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'سياسة الإجازة السنوية للموظفين بدوام كامل',
            ],
            [
                'leave_type_id' => $sickLeave->id,
                'contract_type' => 'full_time',
                'entitled_days' => 120,
                'accrual_rate' => null,
                'accrual_frequency' => null,
                'waiting_period_days' => 0,
                'min_service_months' => 0,
                'max_consecutive_days' => 30,
                'requires_approval' => true,
                'can_be_encashed' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'سياسة الإجازة المرضية للموظفين بدوام كامل',
            ],
            [
                'leave_type_id' => $emergencyLeave->id,
                'contract_type' => 'full_time',
                'entitled_days' => 5,
                'accrual_rate' => null,
                'accrual_frequency' => null,
                'waiting_period_days' => 0,
                'min_service_months' => 0,
                'max_consecutive_days' => 3,
                'requires_approval' => true,
                'can_be_encashed' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'سياسة الإجازة الطارئة للموظفين بدوام كامل',
            ],

            // سياسات الموظفين بدوام جزئي
            [
                'leave_type_id' => $annualLeave->id,
                'contract_type' => 'part_time',
                'entitled_days' => 14,
                'accrual_rate' => 1.17,
                'accrual_frequency' => 'monthly',
                'waiting_period_days' => 90,
                'min_service_months' => 3,
                'max_consecutive_days' => 14,
                'requires_approval' => true,
                'can_be_encashed' => true,
                'encashment_percentage' => 50,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'سياسة الإجازة السنوية للموظفين بدوام جزئي',
            ],
            [
                'leave_type_id' => $sickLeave->id,
                'contract_type' => 'part_time',
                'entitled_days' => 60,
                'accrual_rate' => null,
                'accrual_frequency' => null,
                'waiting_period_days' => 0,
                'min_service_months' => 0,
                'max_consecutive_days' => 15,
                'requires_approval' => true,
                'can_be_encashed' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'سياسة الإجازة المرضية للموظفين بدوام جزئي',
            ],

            // سياسات برنامج تمهير
            [
                'leave_type_id' => $annualLeave->id,
                'contract_type' => 'tamheer',
                'entitled_days' => 10,
                'accrual_rate' => 0.83,
                'accrual_frequency' => 'monthly',
                'waiting_period_days' => 30,
                'min_service_months' => 1,
                'max_consecutive_days' => 5,
                'requires_approval' => true,
                'can_be_encashed' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'سياسة الإجازة السنوية لمتدربي تمهير',
            ],

            // سياسات العمل بالنسبة
            [
                'leave_type_id' => $annualLeave->id,
                'contract_type' => 'percentage',
                'entitled_days' => 15,
                'accrual_rate' => 1.25,
                'accrual_frequency' => 'monthly',
                'waiting_period_days' => 90,
                'min_service_months' => 3,
                'max_consecutive_days' => 15,
                'requires_approval' => true,
                'can_be_encashed' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'سياسة الإجازة للموظفين بالنسبة',
            ],

            // سياسات الأطباء الزائرين (Locum)
            [
                'leave_type_id' => $annualLeave->id,
                'contract_type' => 'locum',
                'entitled_days' => 0,
                'accrual_rate' => null,
                'accrual_frequency' => null,
                'waiting_period_days' => 0,
                'min_service_months' => 0,
                'max_consecutive_days' => 0,
                'requires_approval' => false,
                'can_be_encashed' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'notes' => 'الأطباء الزائرون لا يستحقون إجازة سنوية',
            ],
        ];

        foreach ($policies as $policy) {
            LeavePolicy::updateOrCreate(
                [
                    'leave_type_id' => $policy['leave_type_id'],
                    'contract_type' => $policy['contract_type'],
                ],
                $policy
            );
        }

        $this->command->info('✓ تم إنشاء سياسات الإجازات لجميع أنواع العقود');
    }
}
