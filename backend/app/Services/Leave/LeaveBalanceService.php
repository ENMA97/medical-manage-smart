<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveBalanceAdjustment;
use App\Models\Leave\LeaveType;
use App\Models\Leave\LeavePolicy;
use Illuminate\Support\Facades\DB;
use Exception;

class LeaveBalanceService
{
    /**
     * إنشاء رصيد أولي للموظف
     */
    public function initializeBalance(
        string $employeeId,
        string $leaveTypeId,
        int $year,
        float $entitledDays,
        string $userId
    ): LeaveBalance {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $year, $entitledDays, $userId) {
            // التحقق من عدم وجود رصيد مسبق
            $existing = LeaveBalance::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', $year)
                ->first();

            if ($existing) {
                throw new Exception('يوجد رصيد مسبق لهذه السنة');
            }

            $balance = LeaveBalance::create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
                'entitled_days' => $entitledDays,
                'carried_over_days' => 0,
                'additional_days' => 0,
                'used_days' => 0,
                'pending_days' => 0,
                'remaining_days' => $entitledDays,
                'last_updated_by' => $userId,
            ]);

            // تسجيل التعديل
            LeaveBalanceAdjustment::createAdjustment(
                $balance->id,
                'initial',
                $entitledDays,
                0,
                $userId,
                null,
                'رصيد أولي'
            );

            return $balance;
        });
    }

    /**
     * إنشاء أرصدة لموظف جديد
     */
    public function initializeBalancesForEmployee(
        string $employeeId,
        string $contractType,
        string $userId,
        ?int $year = null
    ): array {
        $year = $year ?? date('Y');
        $balances = [];

        // الحصول على سياسات الإجازات لنوع العقد
        $policies = LeavePolicy::active()
            ->forContractType($contractType)
            ->with('leaveType')
            ->get();

        foreach ($policies as $policy) {
            $balance = $this->initializeBalance(
                $employeeId,
                $policy->leave_type_id,
                $year,
                $policy->days_per_year,
                $userId
            );
            $balances[] = $balance;
        }

        return $balances;
    }

    /**
     * ترحيل الرصيد للسنة الجديدة
     */
    public function carryOverBalance(
        string $employeeId,
        string $leaveTypeId,
        int $fromYear,
        string $userId
    ): ?LeaveBalance {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $fromYear, $userId) {
            $toYear = $fromYear + 1;

            // الحصول على رصيد السنة السابقة
            $oldBalance = LeaveBalance::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', $fromYear)
                ->first();

            if (!$oldBalance || $oldBalance->remaining_days <= 0) {
                return null;
            }

            // التحقق من إمكانية الترحيل
            $leaveType = LeaveType::find($leaveTypeId);
            if (!$leaveType || !$leaveType->can_be_carried_over) {
                return null;
            }

            // حساب المبلغ المرحل
            $carryOverAmount = min(
                $oldBalance->remaining_days,
                $leaveType->max_carry_over_days
            );

            if ($carryOverAmount <= 0) {
                return null;
            }

            // الحصول على أو إنشاء رصيد السنة الجديدة
            $newBalance = LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'year' => $toYear,
                ],
                [
                    'entitled_days' => $leaveType->default_days_per_year,
                    'carried_over_days' => 0,
                    'additional_days' => 0,
                    'used_days' => 0,
                    'pending_days' => 0,
                    'remaining_days' => $leaveType->default_days_per_year,
                    'last_updated_by' => $userId,
                ]
            );

            // إضافة المرحل
            $balanceBefore = $newBalance->remaining_days;
            $newBalance->carried_over_days += $carryOverAmount;
            $newBalance->remaining_days = $newBalance->calculateRemaining();
            $newBalance->last_updated_by = $userId;
            $newBalance->save();

            // تسجيل التعديل
            LeaveBalanceAdjustment::createAdjustment(
                $newBalance->id,
                'carry_over',
                $carryOverAmount,
                $balanceBefore,
                $userId,
                null,
                "ترحيل من سنة {$fromYear}"
            );

            return $newBalance;
        });
    }

    /**
     * إضافة يدوية للرصيد
     */
    public function addManualAdjustment(
        string $balanceId,
        float $days,
        string $reason,
        string $userId
    ): LeaveBalance {
        return DB::transaction(function () use ($balanceId, $days, $reason, $userId) {
            $balance = LeaveBalance::findOrFail($balanceId);
            $balanceBefore = $balance->remaining_days;

            $balance->additional_days += $days;
            $balance->remaining_days = $balance->calculateRemaining();
            $balance->last_updated_by = $userId;
            $balance->save();

            // تسجيل التعديل
            LeaveBalanceAdjustment::createAdjustment(
                $balance->id,
                $days > 0 ? 'manual_add' : 'manual_deduct',
                $days,
                $balanceBefore,
                $userId,
                null,
                $reason
            );

            return $balance;
        });
    }

    /**
     * تصحيح الرصيد
     */
    public function correctBalance(
        string $balanceId,
        float $newRemainingDays,
        string $reason,
        string $userId
    ): LeaveBalance {
        return DB::transaction(function () use ($balanceId, $newRemainingDays, $reason, $userId) {
            $balance = LeaveBalance::findOrFail($balanceId);
            $balanceBefore = $balance->remaining_days;
            $difference = $newRemainingDays - $balanceBefore;

            $balance->remaining_days = $newRemainingDays;
            $balance->last_updated_by = $userId;
            $balance->save();

            // تسجيل التعديل
            LeaveBalanceAdjustment::createAdjustment(
                $balance->id,
                'correction',
                $difference,
                $balanceBefore,
                $userId,
                null,
                $reason
            );

            return $balance;
        });
    }

    /**
     * الحصول على ملخص أرصدة الموظف
     */
    public function getEmployeeBalanceSummary(string $employeeId, ?int $year = null): array
    {
        $year = $year ?? date('Y');

        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        $summary = [];
        foreach ($balances as $balance) {
            $summary[] = [
                'leave_type' => $balance->leaveType->name_ar,
                'leave_type_code' => $balance->leaveType->code,
                'entitled' => $balance->entitled_days,
                'carried_over' => $balance->carried_over_days,
                'additional' => $balance->additional_days,
                'total_available' => $balance->total_available,
                'used' => $balance->used_days,
                'pending' => $balance->pending_days,
                'remaining' => $balance->remaining_days,
            ];
        }

        return $summary;
    }

    /**
     * الحصول على سجل تعديلات الرصيد
     */
    public function getBalanceHistory(string $balanceId): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveBalanceAdjustment::where('leave_balance_id', $balanceId)
            ->with(['leaveRequest', 'performedByUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
