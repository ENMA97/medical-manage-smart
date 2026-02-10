<?php

namespace App\Services\Finance;

use App\Models\Finance\CommissionAdjustment;
use App\Models\Finance\InsuranceClaim;
use App\Models\Finance\InsuranceCompany;
use App\Models\System\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class InsuranceClaimService
{
    /**
     * إنشاء مطالبة جديدة
     */
    public function createClaim(array $data, string $createdBy): InsuranceClaim
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $claim = InsuranceClaim::create([
                ...$data,
                'status' => 'draft',
            ]);

            AuditLog::log('created', $claim, null, $data);

            return $claim;
        });
    }

    /**
     * تدقيق المطالبة (Scrub)
     */
    public function scrubClaim(InsuranceClaim $claim): array
    {
        $result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // التحقق من البيانات الأساسية
        if (empty($claim->diagnosis_code)) {
            $result['warnings'][] = 'رمز التشخيص غير محدد';
        }

        if (empty($claim->procedure_code)) {
            $result['warnings'][] = 'رمز الإجراء غير محدد';
        }

        if (empty($claim->patient_id_number)) {
            $result['errors'][] = 'رقم هوية المريض مطلوب';
            $result['is_valid'] = false;
        }

        if ($claim->claimed_amount <= 0) {
            $result['errors'][] = 'المبلغ المطالب به يجب أن يكون أكبر من صفر';
            $result['is_valid'] = false;
        }

        // التحقق من صلاحية التأمين
        $company = $claim->insuranceCompany;
        if ($company) {
            if (!$company->is_active) {
                $result['errors'][] = 'شركة التأمين غير نشطة';
                $result['is_valid'] = false;
            }

            if (!$company->is_contract_valid) {
                $result['warnings'][] = 'عقد شركة التأمين منتهي أو قريب من الانتهاء';
            }
        }

        // التحقق من تكرار المطالبة
        $duplicate = InsuranceClaim::where('id', '!=', $claim->id)
            ->where('insurance_company_id', $claim->insurance_company_id)
            ->where('patient_id_number', $claim->patient_id_number)
            ->where('service_date', $claim->service_date)
            ->where('service_id', $claim->service_id)
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->exists();

        if ($duplicate) {
            $result['warnings'][] = 'قد تكون هذه المطالبة مكررة';
        }

        // تحديث حالة المطالبة
        $claim->update([
            'scrub_result' => $result,
            'scrub_notes' => $result['is_valid'] ? 'تم التدقيق بنجاح' : 'يوجد أخطاء في المطالبة',
            'status' => 'scrubbed',
        ]);

        return $result;
    }

    /**
     * إرسال المطالبة
     */
    public function submitClaim(InsuranceClaim $claim, string $submittedBy): InsuranceClaim
    {
        if (!in_array($claim->status, ['draft', 'scrubbed'])) {
            throw new Exception('لا يمكن إرسال المطالبة في حالتها الحالية');
        }

        $claim->update([
            'status' => 'submitted',
            'submission_date' => now(),
            'submitted_by' => $submittedBy,
        ]);

        AuditLog::log('updated', $claim, ['status' => $claim->getOriginal('status')], ['status' => 'submitted']);

        return $claim->fresh();
    }

    /**
     * الموافقة على المطالبة
     */
    public function approveClaim(
        InsuranceClaim $claim,
        float $approvedAmount,
        string $approvedBy,
        ?float $deductionAmount = null,
        ?string $deductionReason = null,
        ?string $notes = null
    ): InsuranceClaim {
        if (!in_array($claim->status, ['submitted', 'under_review'])) {
            throw new Exception('المطالبة غير جاهزة للموافقة');
        }

        if ($approvedAmount > $claim->claimed_amount) {
            throw new Exception('المبلغ المعتمد لا يمكن أن يتجاوز المبلغ المطالب به');
        }

        $status = $approvedAmount >= $claim->claimed_amount ? 'approved' : 'partially_approved';

        $oldData = $claim->only(['status', 'approved_amount']);

        $claim->update([
            'approved_amount' => $approvedAmount,
            'deduction_amount' => $deductionAmount ?? 0,
            'deduction_reason' => $deductionReason,
            'status' => $status,
            'approval_date' => now(),
            'approved_by' => $approvedBy,
            'notes' => $notes ?? $claim->notes,
        ]);

        AuditLog::log('approved', $claim, $oldData, [
            'status' => $status,
            'approved_amount' => $approvedAmount,
        ]);

        return $claim->fresh();
    }

    /**
     * رفض المطالبة
     */
    public function rejectClaim(
        InsuranceClaim $claim,
        string $rejectionReason,
        string $rejectedBy
    ): InsuranceClaim {
        $oldStatus = $claim->status;

        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
            'approved_by' => $rejectedBy,
            'approval_date' => now(),
        ]);

        AuditLog::log('rejected', $claim, ['status' => $oldStatus], [
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
        ]);

        // إنشاء تعديل عمولة (clawback) إذا كانت هناك عمولة مدفوعة
        if ($claim->doctor_id && $oldStatus === 'paid') {
            $this->createClawback($claim, $rejectedBy);
        }

        return $claim->fresh();
    }

    /**
     * تسجيل الدفع
     */
    public function recordPayment(
        InsuranceClaim $claim,
        float $paidAmount,
        string $paidBy,
        ?\DateTime $paymentDate = null
    ): InsuranceClaim {
        if (!in_array($claim->status, ['approved', 'partially_approved', 'partially_paid'])) {
            throw new Exception('المطالبة غير موافق عليها');
        }

        $newPaidAmount = ($claim->paid_amount ?? 0) + $paidAmount;
        $status = $newPaidAmount >= $claim->approved_amount ? 'paid' : 'partially_paid';

        $claim->update([
            'paid_amount' => $newPaidAmount,
            'payment_date' => $paymentDate ?? now(),
            'status' => $status,
        ]);

        AuditLog::log('updated', $claim, null, [
            'paid_amount' => $newPaidAmount,
            'status' => $status,
        ]);

        return $claim->fresh();
    }

    /**
     * إنشاء تعديل عمولة (Clawback)
     */
    private function createClawback(InsuranceClaim $claim, string $createdBy): void
    {
        // البحث عن عمولة مرتبطة
        $commission = CommissionAdjustment::where('reference_type', InsuranceClaim::class)
            ->where('reference_id', $claim->id)
            ->where('type', 'commission')
            ->where('status', 'approved')
            ->first();

        if (!$commission) {
            return;
        }

        CommissionAdjustment::create([
            'doctor_id' => $claim->doctor_id,
            'type' => 'clawback',
            'amount' => $commission->amount,
            'reason' => 'استرداد عمولة - رفض مطالبة: ' . $claim->claim_number,
            'reference_type' => InsuranceClaim::class,
            'reference_id' => $claim->id,
            'status' => 'pending',
            'created_by' => $createdBy,
        ]);
    }

    /**
     * الحصول على تقرير التقادم
     */
    public function getAgingReport(?string $insuranceCompanyId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        $buckets = [
            ['label' => '0-30 يوم', 'min' => 0, 'max' => 30],
            ['label' => '31-60 يوم', 'min' => 31, 'max' => 60],
            ['label' => '61-90 يوم', 'min' => 61, 'max' => 90],
            ['label' => '91-120 يوم', 'min' => 91, 'max' => 120],
            ['label' => 'أكثر من 120 يوم', 'min' => 121, 'max' => 99999],
        ];

        $result = [];

        foreach ($buckets as $bucket) {
            $claims = InsuranceClaim::unpaid()
                ->when($insuranceCompanyId, fn($q) => $q->where('insurance_company_id', $insuranceCompanyId))
                ->whereNotNull('submission_date')
                ->whereRaw("DATEDIFF(?, submission_date) BETWEEN ? AND ?", [
                    $asOfDate,
                    $bucket['min'],
                    $bucket['max']
                ])
                ->get();

            $result[] = [
                'bucket' => $bucket['label'],
                'count' => $claims->count(),
                'total_approved' => round($claims->sum('approved_amount'), 2),
                'total_paid' => round($claims->sum('paid_amount'), 2),
                'outstanding' => round($claims->sum('approved_amount') - $claims->sum('paid_amount'), 2),
            ];
        }

        return [
            'as_of_date' => $asOfDate,
            'buckets' => $result,
            'totals' => [
                'count' => collect($result)->sum('count'),
                'total_outstanding' => collect($result)->sum('outstanding'),
            ],
        ];
    }

    /**
     * الحصول على إحصائيات المطالبات
     */
    public function getStatistics(
        string $dateFrom,
        string $dateTo,
        ?string $insuranceCompanyId = null
    ): array {
        $query = InsuranceClaim::whereBetween('service_date', [$dateFrom, $dateTo])
            ->when($insuranceCompanyId, fn($q) => $q->where('insurance_company_id', $insuranceCompanyId));

        $claims = $query->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_claims' => $claims->count(),
            'total_claimed' => round($claims->sum('claimed_amount'), 2),
            'total_approved' => round($claims->sum('approved_amount'), 2),
            'total_paid' => round($claims->sum('paid_amount'), 2),
            'approval_rate' => $claims->sum('claimed_amount') > 0
                ? round(($claims->sum('approved_amount') / $claims->sum('claimed_amount')) * 100, 2)
                : 0,
            'by_status' => $claims->groupBy('status')->map->count(),
        ];
    }
}
