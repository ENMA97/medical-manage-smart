<?php

namespace App\Services\Finance;

use App\Models\Finance\CommissionAdjustment;
use App\Models\Finance\Doctor;
use App\Models\Finance\InsuranceClaim;
use App\Models\System\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class CommissionService
{
    /**
     * حساب عمولة الطبيب من مطالبة
     */
    public function calculateCommission(InsuranceClaim $claim): ?float
    {
        if (!$claim->doctor_id || !$claim->approved_amount) {
            return null;
        }

        $doctor = Doctor::find($claim->doctor_id);
        if (!$doctor) {
            return null;
        }

        // البحث عن نسبة العمولة للخدمة
        $commissionRate = $doctor->default_commission_rate;

        if ($claim->service_id) {
            $serviceCommission = $doctor->services()
                ->where('medical_service_id', $claim->service_id)
                ->first();

            if ($serviceCommission) {
                $commissionRate = $serviceCommission->pivot->commission_rate ?? $commissionRate;
            }
        }

        return round($claim->approved_amount * ($commissionRate / 100), 2);
    }

    /**
     * إنشاء تعديل عمولة
     */
    public function createAdjustment(array $data, string $createdBy): CommissionAdjustment
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $adjustment = CommissionAdjustment::create([
                ...$data,
                'status' => 'pending',
                'created_by' => $createdBy,
            ]);

            AuditLog::log('created', $adjustment, null, $data);

            return $adjustment;
        });
    }

    /**
     * الموافقة على تعديل العمولة
     */
    public function approveAdjustment(
        CommissionAdjustment $adjustment,
        string $approvedBy,
        ?string $notes = null
    ): CommissionAdjustment {
        if ($adjustment->status !== 'pending') {
            throw new Exception('لا يمكن الموافقة على هذا التعديل');
        }

        $oldStatus = $adjustment->status;

        $adjustment->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'notes' => $notes ?? $adjustment->notes,
        ]);

        AuditLog::log('approved', $adjustment, ['status' => $oldStatus], ['status' => 'approved']);

        return $adjustment->fresh();
    }

    /**
     * رفض تعديل العمولة
     */
    public function rejectAdjustment(
        CommissionAdjustment $adjustment,
        string $rejectedBy,
        string $rejectionReason
    ): CommissionAdjustment {
        if ($adjustment->status !== 'pending') {
            throw new Exception('لا يمكن رفض هذا التعديل');
        }

        $adjustment->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'notes' => $rejectionReason,
        ]);

        AuditLog::log('rejected', $adjustment, null, [
            'status' => 'rejected',
            'reason' => $rejectionReason,
        ]);

        return $adjustment->fresh();
    }

    /**
     * ملخص عمولات الطبيب
     */
    public function getDoctorCommissionSummary(
        string $doctorId,
        string $dateFrom,
        string $dateTo
    ): array {
        // العمولات من المطالبات المعتمدة
        $claims = InsuranceClaim::where('doctor_id', $doctorId)
            ->whereIn('status', ['approved', 'partially_approved', 'paid', 'partially_paid'])
            ->whereBetween('service_date', [$dateFrom, $dateTo])
            ->get();

        $claimsCommission = $claims->sum(fn($claim) => $this->calculateCommission($claim) ?? 0);

        // تعديلات العمولات
        $adjustments = CommissionAdjustment::where('doctor_id', $doctorId)
            ->where('status', 'approved')
            ->whereBetween('approved_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->get();

        $bonuses = $adjustments->whereIn('type', ['bonus', 'incentive'])->sum('amount');
        $penalties = $adjustments->whereIn('type', ['clawback', 'penalty'])->sum('amount');

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'claims_count' => $claims->count(),
            'claims_revenue' => round($claims->sum('approved_amount'), 2),
            'claims_commission' => round($claimsCommission, 2),
            'bonuses' => round($bonuses, 2),
            'penalties' => round($penalties, 2),
            'net_commission' => round($claimsCommission + $bonuses - $penalties, 2),
        ];
    }

    /**
     * تقرير عمولات جميع الأطباء
     */
    public function getAllDoctorsCommissionReport(string $dateFrom, string $dateTo): array
    {
        $doctors = Doctor::where('is_active', true)->get();

        $report = [];

        foreach ($doctors as $doctor) {
            $summary = $this->getDoctorCommissionSummary($doctor->id, $dateFrom, $dateTo);

            if ($summary['claims_count'] > 0 || $summary['net_commission'] != 0) {
                $report[] = [
                    'doctor' => [
                        'id' => $doctor->id,
                        'name' => $doctor->employee->name ?? $doctor->id,
                        'specialty' => $doctor->specialty,
                    ],
                    ...$summary,
                ];
            }
        }

        // ترتيب حسب صافي العمولة
        usort($report, fn($a, $b) => $b['net_commission'] <=> $a['net_commission']);

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'doctors' => $report,
            'totals' => [
                'total_revenue' => round(collect($report)->sum('claims_revenue'), 2),
                'total_commission' => round(collect($report)->sum('net_commission'), 2),
            ],
        ];
    }
}
