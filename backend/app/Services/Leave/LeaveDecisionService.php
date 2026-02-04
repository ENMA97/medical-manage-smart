<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveRequest;
use App\Models\Leave\LeaveDecision;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveBalanceAdjustment;
use Illuminate\Support\Facades\DB;
use Exception;

class LeaveDecisionService
{
    /**
     * إنشاء قرار إجازة جديد
     */
    public function createDecision(LeaveRequest $request, string $userId): LeaveDecision
    {
        if ($request->status !== 'form_completed') {
            throw new Exception('نموذج الطلب غير مكتمل');
        }

        if ($request->decision) {
            throw new Exception('يوجد قرار مسبق لهذا الطلب');
        }

        return DB::transaction(function () use ($request, $userId) {
            $employeeType = $this->determineEmployeeType($request);
            $initialStatus = $this->getInitialStatus($employeeType);

            $decision = LeaveDecision::create([
                'decision_number' => LeaveDecision::generateDecisionNumber(),
                'leave_request_id' => $request->id,
                'employee_type' => $employeeType,
                'status' => $initialStatus,
                'requires_gm_approval' => false,
                'forwarded_to_gm' => false,
                'created_by' => $userId,
            ]);

            // تحديث حالة الطلب
            $request->status = 'decision_pending';
            $request->save();

            return $decision;
        });
    }

    /**
     * تحديد نوع الموظف
     */
    protected function determineEmployeeType(LeaveRequest $request): string
    {
        $employee = $request->employee;
        $type = $employee->type ?? 'administrative';

        if (in_array($type, ['doctor', 'physician'])) {
            return 'doctor';
        }

        if (in_array($type, ['nurse', 'technician', 'medical'])) {
            return 'medical_staff';
        }

        return 'administrative';
    }

    /**
     * الحصول على الحالة الأولية للقرار
     */
    protected function getInitialStatus(string $employeeType): string
    {
        if (in_array($employeeType, ['doctor', 'medical_staff'])) {
            return 'pending_medical_director';
        }

        return 'pending_admin_manager';
    }

    /**
     * اعتماد المدير الإداري
     */
    public function approveByAdminManager(
        LeaveDecision $decision,
        string $approverId,
        ?string $comment = null,
        bool $forwardToGm = false
    ): LeaveDecision {
        if ($decision->status !== 'pending_admin_manager') {
            throw new Exception('القرار ليس بانتظار المدير الإداري');
        }

        return DB::transaction(function () use ($decision, $approverId, $comment, $forwardToGm) {
            $decision->approveByAdminManager($approverId, $comment, $forwardToGm);

            if (!$forwardToGm) {
                // الموافقة النهائية - تفعيل الإجازة
                $this->finalizeApproval($decision);
            }

            return $decision;
        });
    }

    /**
     * اعتماد المدير الطبي
     */
    public function approveByMedicalDirector(
        LeaveDecision $decision,
        string $approverId,
        ?string $comment = null,
        bool $forwardToGm = false
    ): LeaveDecision {
        if ($decision->status !== 'pending_medical_director') {
            throw new Exception('القرار ليس بانتظار المدير الطبي');
        }

        return DB::transaction(function () use ($decision, $approverId, $comment, $forwardToGm) {
            $decision->approveByMedicalDirector($approverId, $comment, $forwardToGm);

            if (!$forwardToGm) {
                // الموافقة النهائية - تفعيل الإجازة
                $this->finalizeApproval($decision);
            }

            return $decision;
        });
    }

    /**
     * اعتماد المدير العام
     */
    public function approveByGeneralManager(
        LeaveDecision $decision,
        string $approverId,
        ?string $comment = null
    ): LeaveDecision {
        if ($decision->status !== 'pending_general_manager') {
            throw new Exception('القرار ليس بانتظار المدير العام');
        }

        return DB::transaction(function () use ($decision, $approverId, $comment) {
            $decision->approveByGeneralManager($approverId, $comment);
            $this->finalizeApproval($decision);

            return $decision;
        });
    }

    /**
     * رفض القرار
     */
    public function rejectDecision(
        LeaveDecision $decision,
        string $approverId,
        string $comment,
        string $role
    ): LeaveDecision {
        return DB::transaction(function () use ($decision, $approverId, $comment, $role) {
            $decision->reject($approverId, $comment, $role);

            // تحديث حالة الطلب
            $request = $decision->leaveRequest;
            $request->status = 'rejected';
            $request->save();

            // إلغاء الرصيد المعلق
            $this->cancelPendingBalance($request);

            return $decision;
        });
    }

    /**
     * تفعيل الإجازة بعد الموافقة النهائية
     */
    protected function finalizeApproval(LeaveDecision $decision): void
    {
        $request = $decision->leaveRequest;

        // تحديث حالة الطلب
        $request->status = 'approved';
        $request->save();

        // تحويل الرصيد المعلق إلى مستخدم
        $this->confirmBalance($request, $decision->final_approved_by);
    }

    /**
     * تأكيد خصم الرصيد
     */
    protected function confirmBalance(LeaveRequest $request, string $userId): void
    {
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', date('Y'))
            ->first();

        if ($balance) {
            $balanceBefore = $balance->remaining_days;
            $balance->confirmPending($request->total_days, $userId);

            // تسجيل التعديل
            LeaveBalanceAdjustment::createAdjustment(
                $balance->id,
                'used',
                -$request->total_days,
                $balanceBefore,
                $userId,
                $request->id,
                'استخدام إجازة معتمدة'
            );
        }
    }

    /**
     * إلغاء الرصيد المعلق
     */
    protected function cancelPendingBalance(LeaveRequest $request): void
    {
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', date('Y'))
            ->first();

        if ($balance) {
            $balance->cancelPending($request->total_days);
        }
    }

    /**
     * الحصول على القرارات المعلقة للمدير الإداري
     */
    public function getPendingForAdminManager(): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveDecision::where('status', 'pending_admin_manager')
            ->with(['leaveRequest.employee', 'leaveRequest.leaveType'])
            ->get();
    }

    /**
     * الحصول على القرارات المعلقة للمدير الطبي
     */
    public function getPendingForMedicalDirector(): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveDecision::where('status', 'pending_medical_director')
            ->with(['leaveRequest.employee', 'leaveRequest.leaveType'])
            ->get();
    }

    /**
     * الحصول على القرارات المعلقة للمدير العام
     */
    public function getPendingForGeneralManager(): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveDecision::where('status', 'pending_general_manager')
            ->with(['leaveRequest.employee', 'leaveRequest.leaveType'])
            ->get();
    }
}
