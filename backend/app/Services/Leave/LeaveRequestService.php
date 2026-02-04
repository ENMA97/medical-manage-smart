<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveRequest;
use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveDecision;
use App\Models\Leave\EmployeeApprover;
use App\Models\Leave\LeaveApprovalWorkflow;
use Illuminate\Support\Facades\DB;
use Exception;

class LeaveRequestService
{
    /**
     * إنشاء طلب إجازة جديد
     */
    public function createRequest(array $data, string $userId): LeaveRequest
    {
        return DB::transaction(function () use ($data, $userId) {
            // التحقق من الرصيد
            $balance = LeaveBalance::where('employee_id', $data['employee_id'])
                ->where('leave_type_id', $data['leave_type_id'])
                ->where('year', date('Y'))
                ->first();

            if (!$balance || !$balance->hasEnoughBalance($data['total_days'])) {
                throw new Exception('رصيد الإجازة غير كافي');
            }

            // إنشاء الطلب
            $request = LeaveRequest::create([
                'request_number' => LeaveRequest::generateRequestNumber(),
                'employee_id' => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_days' => $data['total_days'],
                'is_half_day' => $data['is_half_day'] ?? false,
                'half_day_period' => $data['half_day_period'] ?? null,
                'reason' => $data['reason'] ?? null,
                'reason_ar' => $data['reason_ar'] ?? null,
                'contact_during_leave' => $data['contact_during_leave'] ?? null,
                'address_during_leave' => $data['address_during_leave'] ?? null,
                'delegate_employee_id' => $data['delegate_employee_id'] ?? null,
                'attachments' => $data['attachments'] ?? null,
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            // إضافة للرصيد المعلق
            $balance->addPending($data['total_days']);

            return $request;
        });
    }

    /**
     * تقديم الطلب للموافقة
     */
    public function submitRequest(LeaveRequest $request): LeaveRequest
    {
        if ($request->status !== 'draft') {
            throw new Exception('لا يمكن تقديم هذا الطلب');
        }

        return DB::transaction(function () use ($request) {
            // إنشاء سلسلة الموافقات
            $this->createApprovalChain($request);

            // تحديث الحالة
            $request->status = 'pending_supervisor';
            $request->save();

            return $request;
        });
    }

    /**
     * إنشاء سلسلة الموافقات
     */
    protected function createApprovalChain(LeaveRequest $request): void
    {
        $employee = $request->employee;
        $employeeType = $employee->type ?? 'administrative';

        // الحصول على سلسلة الموافقات
        $workflow = LeaveApprovalWorkflow::getWorkflowForEmployeeType($employeeType);
        $chain = $workflow ? $workflow->getRequestChain() : $this->getDefaultChain();

        foreach ($chain as $step) {
            $approverId = $this->getApproverForStep($request->employee_id, $step['type']);

            if ($approverId) {
                LeaveApproval::create([
                    'leave_request_id' => $request->id,
                    'sequence' => $step['sequence'],
                    'approver_type' => $step['type'],
                    'action_type' => $step['action'],
                    'approver_id' => $approverId,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * الحصول على المعتمد للخطوة
     */
    protected function getApproverForStep(string $employeeId, string $stepType): ?string
    {
        $roleMapping = [
            'supervisor' => 'direct_manager',
            'admin_manager' => 'admin_manager',
            'hr_officer' => 'hr_officer',
            'delegate' => 'delegate',
        ];

        $role = $roleMapping[$stepType] ?? $stepType;
        $approver = EmployeeApprover::getApproverForEmployee($employeeId, $role);

        return $approver?->id;
    }

    /**
     * سلسلة الموافقات الافتراضية
     */
    protected function getDefaultChain(): array
    {
        return [
            ['sequence' => 1, 'type' => 'supervisor', 'action' => 'recommendation'],
            ['sequence' => 2, 'type' => 'admin_manager', 'action' => 'approval'],
            ['sequence' => 3, 'type' => 'hr_officer', 'action' => 'endorsement'],
            ['sequence' => 4, 'type' => 'delegate', 'action' => 'coverage_confirmation'],
        ];
    }

    /**
     * معالجة توصية المشرف
     */
    public function processSupervisorRecommendation(
        LeaveRequest $request,
        string $approverId,
        bool $approved,
        ?string $comment = null,
        ?string $jobTasks = null,
        ?string $ipAddress = null
    ): LeaveRequest {
        return DB::transaction(function () use ($request, $approverId, $approved, $comment, $jobTasks, $ipAddress) {
            $approval = $request->approvals()
                ->where('approver_type', 'supervisor')
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            if ($approved) {
                $approval->recommend($comment, $jobTasks, $ipAddress);

                // تحديث المهام في الطلب
                if ($jobTasks) {
                    $request->job_tasks = $jobTasks;
                    $request->save();
                }

                $request->status = 'pending_admin_manager';
            } else {
                $approval->reject($comment, $ipAddress);
                $request->status = 'rejected';

                // إلغاء الرصيد المعلق
                $this->cancelPendingBalance($request);
            }

            $request->save();
            return $request;
        });
    }

    /**
     * معالجة موافقة المدير الإداري
     */
    public function processAdminManagerApproval(
        LeaveRequest $request,
        string $approverId,
        bool $approved,
        ?string $comment = null,
        ?string $ipAddress = null
    ): LeaveRequest {
        return DB::transaction(function () use ($request, $approverId, $approved, $comment, $ipAddress) {
            $approval = $request->approvals()
                ->where('approver_type', 'admin_manager')
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            if ($approved) {
                $approval->approve($comment, $ipAddress);
                $request->status = 'pending_hr';
            } else {
                $approval->reject($comment, $ipAddress);
                $request->status = 'rejected';
                $this->cancelPendingBalance($request);
            }

            $request->save();
            return $request;
        });
    }

    /**
     * معالجة تعميد الموارد البشرية
     */
    public function processHrEndorsement(
        LeaveRequest $request,
        string $approverId,
        bool $approved,
        ?string $comment = null,
        ?string $ipAddress = null
    ): LeaveRequest {
        return DB::transaction(function () use ($request, $approverId, $approved, $comment, $ipAddress) {
            $approval = $request->approvals()
                ->where('approver_type', 'hr_officer')
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            if ($approved) {
                $approval->endorse($comment, $ipAddress);
                $request->status = 'pending_delegate';
            } else {
                $approval->reject($comment, $ipAddress);
                $request->status = 'rejected';
                $this->cancelPendingBalance($request);
            }

            $request->save();
            return $request;
        });
    }

    /**
     * معالجة تأكيد القائم بالعمل
     */
    public function processDelegateConfirmation(
        LeaveRequest $request,
        string $approverId,
        bool $approved,
        ?string $comment = null,
        ?string $ipAddress = null
    ): LeaveRequest {
        return DB::transaction(function () use ($request, $approverId, $approved, $comment, $ipAddress) {
            $approval = $request->approvals()
                ->where('approver_type', 'delegate')
                ->where('approver_id', $approverId)
                ->where('status', 'pending')
                ->firstOrFail();

            if ($approved) {
                $approval->confirmCoverage($comment, $ipAddress);
                $request->confirmDelegate();
                $request->status = 'form_completed';
            } else {
                $approval->reject($comment, $ipAddress);
                $request->status = 'rejected';
                $this->cancelPendingBalance($request);
            }

            $request->save();
            return $request;
        });
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
     * إلغاء طلب الإجازة
     */
    public function cancelRequest(LeaveRequest $request, string $reason, string $userId): LeaveRequest
    {
        if (!$request->canBeCancelled()) {
            throw new Exception('لا يمكن إلغاء هذا الطلب');
        }

        return DB::transaction(function () use ($request, $reason, $userId) {
            $request->cancel($reason, $userId);
            $this->cancelPendingBalance($request);

            return $request;
        });
    }

    /**
     * الحصول على الطلبات المعلقة للمعتمد
     */
    public function getPendingRequestsForApprover(string $approverId): \Illuminate\Database\Eloquent\Collection
    {
        return LeaveRequest::whereHas('approvals', function ($query) use ($approverId) {
            $query->where('approver_id', $approverId)
                ->where('status', 'pending');
        })
        ->with(['employee', 'leaveType', 'approvals'])
        ->get();
    }
}
