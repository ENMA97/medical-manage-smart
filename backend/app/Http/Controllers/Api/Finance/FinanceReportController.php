<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CommissionAdjustment;
use App\Models\Finance\InsuranceClaim;
use App\Models\Finance\MedicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    /**
     * تقرير الربحية
     */
    public function profitability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'cost_center_id' => ['nullable', 'uuid', 'exists:cost_centers,id'],
        ]);

        $query = InsuranceClaim::query()
            ->whereIn('status', ['approved', 'partially_approved', 'paid', 'partially_paid'])
            ->whereBetween('service_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['cost_center_id'] ?? null, function ($q, $costCenterId) {
                $q->whereHas('service', fn($sq) => $sq->where('cost_center_id', $costCenterId));
            });

        // ملخص عام
        $summary = [
            'total_revenue' => (clone $query)->sum('approved_amount'),
            'total_collected' => (clone $query)->sum('paid_amount'),
            'total_outstanding' => (clone $query)->selectRaw('SUM(approved_amount - COALESCE(paid_amount, 0)) as outstanding')->value('outstanding'),
            'claims_count' => (clone $query)->count(),
            'collection_rate' => 0,
        ];

        if ($summary['total_revenue'] > 0) {
            $summary['collection_rate'] = round(($summary['total_collected'] / $summary['total_revenue']) * 100, 2);
        }

        // حسب شركة التأمين
        $byInsuranceCompany = (clone $query)
            ->with('insuranceCompany')
            ->select(
                'insurance_company_id',
                DB::raw('COUNT(*) as claims_count'),
                DB::raw('SUM(claimed_amount) as total_claimed'),
                DB::raw('SUM(approved_amount) as total_approved'),
                DB::raw('SUM(COALESCE(paid_amount, 0)) as total_paid'),
                DB::raw('SUM(approved_amount - COALESCE(paid_amount, 0)) as outstanding')
            )
            ->groupBy('insurance_company_id')
            ->orderByDesc('total_approved')
            ->get()
            ->map(fn($row) => [
                'insurance_company' => $row->insuranceCompany,
                'claims_count' => $row->claims_count,
                'total_claimed' => round($row->total_claimed, 2),
                'total_approved' => round($row->total_approved, 2),
                'total_paid' => round($row->total_paid, 2),
                'outstanding' => round($row->outstanding, 2),
                'approval_rate' => $row->total_claimed > 0 ? round(($row->total_approved / $row->total_claimed) * 100, 2) : 0,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'by_insurance_company' => $byInsuranceCompany,
            ],
        ]);
    }

    /**
     * الإيرادات حسب الخدمة
     */
    public function revenueByService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'category' => ['nullable', 'string'],
        ]);

        $claims = InsuranceClaim::with('service')
            ->whereIn('status', ['approved', 'partially_approved', 'paid', 'partially_paid'])
            ->whereBetween('service_date', [$validated['date_from'], $validated['date_to']])
            ->whereNotNull('service_id')
            ->when($validated['category'] ?? null, function ($q, $category) {
                $q->whereHas('service', fn($sq) => $sq->where('category', $category));
            })
            ->select(
                'service_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(approved_amount) as total_revenue'),
                DB::raw('SUM(COALESCE(paid_amount, 0)) as total_collected')
            )
            ->groupBy('service_id')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn($row) => [
                'service' => $row->service,
                'count' => $row->count,
                'total_revenue' => round($row->total_revenue, 2),
                'total_collected' => round($row->total_collected, 2),
                'avg_revenue' => $row->count > 0 ? round($row->total_revenue / $row->count, 2) : 0,
            ]);

        $totals = [
            'total_revenue' => $claims->sum('total_revenue'),
            'total_collected' => $claims->sum('total_collected'),
            'services_count' => $claims->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'by_service' => $claims,
            ],
        ]);
    }

    /**
     * الإيرادات حسب الطبيب
     */
    public function revenueByDoctor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
        ]);

        $claims = InsuranceClaim::with('doctor.employee')
            ->whereIn('status', ['approved', 'partially_approved', 'paid', 'partially_paid'])
            ->whereBetween('service_date', [$validated['date_from'], $validated['date_to']])
            ->whereNotNull('doctor_id')
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('doctor', fn($dq) => $dq->where('department_id', $deptId));
            })
            ->select(
                'doctor_id',
                DB::raw('COUNT(*) as claims_count'),
                DB::raw('SUM(approved_amount) as total_revenue'),
                DB::raw('SUM(COALESCE(paid_amount, 0)) as total_collected')
            )
            ->groupBy('doctor_id')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn($row) => [
                'doctor' => $row->doctor,
                'claims_count' => $row->claims_count,
                'total_revenue' => round($row->total_revenue, 2),
                'total_collected' => round($row->total_collected, 2),
            ]);

        // جلب تعديلات العمولات
        $adjustmentsByDoctor = CommissionAdjustment::where('status', 'approved')
            ->whereBetween('approved_at', [$validated['date_from'], $validated['date_to'] . ' 23:59:59'])
            ->select(
                'doctor_id',
                DB::raw("SUM(CASE WHEN type IN ('clawback', 'penalty') THEN -amount ELSE amount END) as net_adjustment")
            )
            ->groupBy('doctor_id')
            ->pluck('net_adjustment', 'doctor_id');

        $result = $claims->map(function ($item) use ($adjustmentsByDoctor) {
            $doctorId = $item['doctor']->id ?? null;
            $adjustment = $adjustmentsByDoctor[$doctorId] ?? 0;
            return array_merge($item, [
                'commission_adjustments' => round($adjustment, 2),
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * ملخص المطالبات
     */
    public function claimsSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'insurance_company_id' => ['nullable', 'uuid', 'exists:insurance_companies,id'],
        ]);

        $query = InsuranceClaim::query()
            ->whereBetween('service_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['insurance_company_id'] ?? null, fn($q, $id) => $q->where('insurance_company_id', $id));

        // حسب الحالة
        $byStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(claimed_amount) as total'))
            ->groupBy('status')
            ->get();

        // يومياً
        $daily = (clone $query)
            ->select(
                DB::raw('DATE(service_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(claimed_amount) as claimed'),
                DB::raw('SUM(approved_amount) as approved')
            )
            ->groupBy(DB::raw('DATE(service_date)'))
            ->orderBy('date')
            ->get();

        // الإجماليات
        $totals = [
            'total_claims' => (clone $query)->count(),
            'total_claimed' => (clone $query)->sum('claimed_amount'),
            'total_approved' => (clone $query)->sum('approved_amount'),
            'total_paid' => (clone $query)->sum('paid_amount'),
            'pending_count' => (clone $query)->pending()->count(),
            'rejected_count' => (clone $query)->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'by_status' => $byStatus,
                'daily' => $daily,
            ],
        ]);
    }

    /**
     * تقرير التقادم
     */
    public function agingReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'as_of_date' => ['sometimes', 'date'],
            'insurance_company_id' => ['nullable', 'uuid', 'exists:insurance_companies,id'],
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->toDateString();

        $agingBuckets = [
            ['label' => '0-30 يوم', 'min' => 0, 'max' => 30],
            ['label' => '31-60 يوم', 'min' => 31, 'max' => 60],
            ['label' => '61-90 يوم', 'min' => 61, 'max' => 90],
            ['label' => '91-120 يوم', 'min' => 91, 'max' => 120],
            ['label' => 'أكثر من 120 يوم', 'min' => 121, 'max' => 99999],
        ];

        $result = [];

        foreach ($agingBuckets as $bucket) {
            $claims = InsuranceClaim::unpaid()
                ->when($validated['insurance_company_id'] ?? null, fn($q, $id) => $q->where('insurance_company_id', $id))
                ->whereNotNull('submission_date')
                ->whereRaw("DATEDIFF(?, submission_date) BETWEEN ? AND ?", [$asOfDate, $bucket['min'], $bucket['max']])
                ->get();

            $result[] = [
                'bucket' => $bucket['label'],
                'count' => $claims->count(),
                'total_approved' => round($claims->sum('approved_amount'), 2),
                'total_paid' => round($claims->sum('paid_amount'), 2),
                'outstanding' => round($claims->sum('approved_amount') - $claims->sum('paid_amount'), 2),
            ];
        }

        $totals = [
            'count' => collect($result)->sum('count'),
            'total_outstanding' => collect($result)->sum('outstanding'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'buckets' => $result,
                'totals' => $totals,
            ],
        ]);
    }

    /**
     * تحليل التكاليف
     */
    public function costAnalysis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'cost_center_id' => ['nullable', 'uuid', 'exists:cost_centers,id'],
        ]);

        // تحليل الخدمات
        $services = MedicalService::with('costCenter')
            ->when($validated['cost_center_id'] ?? null, fn($q, $id) => $q->where('cost_center_id', $id))
            ->get()
            ->map(function ($service) use ($validated) {
                $claims = InsuranceClaim::where('service_id', $service->id)
                    ->whereIn('status', ['approved', 'partially_approved', 'paid'])
                    ->whereBetween('service_date', [$validated['date_from'], $validated['date_to']])
                    ->get();

                $revenue = $claims->sum('approved_amount');
                $count = $claims->count();
                $estimatedCost = ($service->cost ?? 0) * $count;

                return [
                    'service' => $service,
                    'transactions' => $count,
                    'revenue' => round($revenue, 2),
                    'estimated_cost' => round($estimatedCost, 2),
                    'gross_profit' => round($revenue - $estimatedCost, 2),
                    'margin' => $revenue > 0 ? round((($revenue - $estimatedCost) / $revenue) * 100, 2) : 0,
                ];
            })
            ->filter(fn($item) => $item['transactions'] > 0)
            ->sortByDesc('gross_profit')
            ->values();

        $totals = [
            'total_revenue' => $services->sum('revenue'),
            'total_cost' => $services->sum('estimated_cost'),
            'total_profit' => $services->sum('gross_profit'),
            'overall_margin' => 0,
        ];

        if ($totals['total_revenue'] > 0) {
            $totals['overall_margin'] = round(($totals['total_profit'] / $totals['total_revenue']) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'by_service' => $services,
            ],
        ]);
    }
}
