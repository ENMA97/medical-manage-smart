<?php

namespace App\Services\HR;

use App\Models\HR\ClearanceRequest;
use App\Models\HR\Employee;
use Illuminate\Support\Facades\DB;
use Exception;

class ClearanceService
{
    /**
     * إنشاء طلب إخلاء طرف
     */
    public function createClearanceRequest(
        string $employeeId,
        string $reason,
        string $lastWorkingDay,
        string $initiatedBy,
        ?string $otherReason = null
    ): ClearanceRequest {
        $employee = Employee::findOrFail($employeeId);

        // التحقق من عدم وجود طلب قيد التنفيذ
        $existingRequest = ClearanceRequest::where('employee_id', $employeeId)
            ->pending()
            ->first();

        if ($existingRequest) {
            throw new Exception('يوجد طلب إخلاء طرف قيد التنفيذ لهذا الموظف');
        }

        return ClearanceRequest::create([
            'request_number' => $this->generateRequestNumber(),
            'employee_id' => $employeeId,
            'reason' => $reason,
            'other_reason' => $otherReason,
            'last_working_day' => $lastWorkingDay,
            'status' => ClearanceRequest::STATUS_PENDING,
            'initiated_by' => $initiatedBy,
            'initiated_at' => now(),
        ]);
    }

    /**
     * موافقة قسم المالية
     */
    public function approveByFinance(
        ClearanceRequest $request,
        string $approverId,
        ?string $notes = null,
        ?float $amountDue = null
    ): ClearanceRequest {
        if ($request->finance_approved_at) {
            throw new Exception('تمت الموافقة من قسم المالية مسبقاً');
        }

        $request->approveByFinance($approverId, $notes, $amountDue);

        return $request->fresh();
    }

    /**
     * موافقة الموارد البشرية
     */
    public function approveByHr(
        ClearanceRequest $request,
        string $approverId,
        ?string $notes = null,
        ?float $vacationBalance = null
    ): ClearanceRequest {
        if (!$request->finance_approved_at) {
            throw new Exception('يجب موافقة قسم المالية أولاً');
        }

        if ($request->hr_approved_at) {
            throw new Exception('تمت الموافقة من الموارد البشرية مسبقاً');
        }

        // حساب رصيد الإجازات إذا لم يُحدد
        if ($vacationBalance === null) {
            $vacationBalance = $this->calculateVacationBalance($request->employee_id);
        }

        $request->approveByHr($approverId, $notes, $vacationBalance);

        return $request->fresh();
    }

    /**
     * موافقة تقنية المعلومات
     */
    public function approveByIt(
        ClearanceRequest $request,
        string $approverId,
        ?string $notes = null
    ): ClearanceRequest {
        if (!$request->hr_approved_at) {
            throw new Exception('يجب موافقة الموارد البشرية أولاً');
        }

        if ($request->it_approved_at) {
            throw new Exception('تمت الموافقة من تقنية المعلومات مسبقاً');
        }

        $request->approveByIt($approverId, $notes);

        return $request->fresh();
    }

    /**
     * تسوية العهد
     */
    public function clearCustody(
        ClearanceRequest $request,
        string $clearerId,
        ?string $notes = null
    ): ClearanceRequest {
        if (!$request->it_approved_at) {
            throw new Exception('يجب موافقة تقنية المعلومات أولاً');
        }

        if ($request->custody_cleared_at) {
            throw new Exception('تمت تسوية العهد مسبقاً');
        }

        $employee = $request->employee;

        // حساب قيمة العهد غير المسترجعة
        $totalValue = $employee->getOutstandingCustodiesValue();

        // تسوية جميع العهد النشطة
        $deductions = 0;
        foreach ($employee->activeCustodies as $custody) {
            // تسجيل العهدة كمسترجعة أو محسومة
            if ($custody->status === 'active') {
                $custody->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                    'received_by' => $clearerId,
                    'condition_on_return' => 'clearance_settlement',
                ]);
            }
        }

        $request->clearCustody($clearerId, $notes, $totalValue, $deductions);

        return $request->fresh();
    }

    /**
     * إكمال إخلاء الطرف
     */
    public function complete(ClearanceRequest $request, string $completerId): ClearanceRequest
    {
        if (!$request->custody_cleared_at) {
            throw new Exception('يجب تسوية العهد أولاً');
        }

        if ($request->status === ClearanceRequest::STATUS_COMPLETED) {
            throw new Exception('تم إكمال إخلاء الطرف مسبقاً');
        }

        return DB::transaction(function () use ($request, $completerId) {
            $settlementAmount = $request->calculateSettlement();
            $request->complete($completerId, $settlementAmount);

            return $request->fresh();
        });
    }

    /**
     * رفض طلب إخلاء الطرف
     */
    public function reject(ClearanceRequest $request, string $reason): ClearanceRequest
    {
        if ($request->status === ClearanceRequest::STATUS_COMPLETED) {
            throw new Exception('لا يمكن رفض طلب مكتمل');
        }

        $request->reject($reason);

        return $request->fresh();
    }

    /**
     * حساب رصيد الإجازات للتسوية
     */
    protected function calculateVacationBalance(string $employeeId): float
    {
        $employee = Employee::findOrFail($employeeId);
        $year = date('Y');

        $annualBalance = $employee->leaveBalances()
            ->whereHas('leaveType', function ($query) {
                $query->where('code', 'ANNUAL');
            })
            ->where('year', $year)
            ->first();

        return $annualBalance ? $annualBalance->remaining_days : 0;
    }

    /**
     * توليد رقم الطلب
     */
    protected function generateRequestNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastRequest = ClearanceRequest::where('request_number', 'like', "CLR{$year}{$month}%")
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'CLR' . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على الطلبات المعلقة لقسم معين
     */
    public function getPendingForDepartment(string $department): \Illuminate\Database\Eloquent\Collection
    {
        return ClearanceRequest::pendingFor($department)
            ->with(['employee', 'employee.department', 'employee.position'])
            ->orderBy('initiated_at')
            ->get();
    }

    /**
     * إحصائيات إخلاء الطرف
     */
    public function getStatistics(): array
    {
        return [
            'total' => ClearanceRequest::count(),
            'pending' => ClearanceRequest::pending()->count(),
            'completed' => ClearanceRequest::completed()->count(),
            'pending_finance' => ClearanceRequest::pendingFor('finance')->count(),
            'pending_hr' => ClearanceRequest::pendingFor('hr')->count(),
            'pending_it' => ClearanceRequest::pendingFor('it')->count(),
            'pending_custody' => ClearanceRequest::pendingFor('custody')->count(),
            'by_reason' => ClearanceRequest::select('reason', DB::raw('count(*) as count'))
                ->groupBy('reason')
                ->pluck('count', 'reason'),
        ];
    }
}
