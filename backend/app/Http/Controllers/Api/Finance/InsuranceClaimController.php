<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\InsuranceClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InsuranceClaimController extends Controller
{
    /**
     * قائمة المطالبات
     */
    public function index(Request $request): JsonResponse
    {
        $query = InsuranceClaim::with(['insuranceCompany', 'service', 'doctor.employee'])
            ->when($request->insurance_company_id, fn($q, $id) => $q->where('insurance_company_id', $id))
            ->when($request->doctor_id, fn($q, $id) => $q->where('doctor_id', $id))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->date_from, fn($q, $date) => $q->where('service_date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('service_date', '<=', $date))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('claim_number', 'like', "%{$search}%")
                        ->orWhere('patient_name', 'like', "%{$search}%")
                        ->orWhere('patient_id_number', 'like', "%{$search}%")
                        ->orWhere('policy_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        $claims = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }

    /**
     * المطالبات المعلقة
     */
    public function pending(): JsonResponse
    {
        $claims = InsuranceClaim::with(['insuranceCompany', 'service'])
            ->pending()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }

    /**
     * تقرير التقادم (Aging)
     */
    public function aging(Request $request): JsonResponse
    {
        $agingBuckets = [
            '0-30' => [0, 30],
            '31-60' => [31, 60],
            '61-90' => [61, 90],
            '91-120' => [91, 120],
            '120+' => [121, 9999],
        ];

        $result = [];

        foreach ($agingBuckets as $bucket => $range) {
            $claims = InsuranceClaim::unpaid()
                ->when($request->insurance_company_id, fn($q, $id) => $q->where('insurance_company_id', $id))
                ->whereNotNull('submission_date')
                ->whereRaw('DATEDIFF(CURRENT_DATE, submission_date) BETWEEN ? AND ?', $range)
                ->get();

            $result[$bucket] = [
                'count' => $claims->count(),
                'total_approved' => $claims->sum('approved_amount'),
                'total_paid' => $claims->sum('paid_amount'),
                'total_outstanding' => $claims->sum('approved_amount') - $claims->sum('paid_amount'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * إنشاء مطالبة
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'insurance_company_id' => ['required', 'uuid', 'exists:insurance_companies,id'],
            'patient_name' => ['required', 'string', 'max:200'],
            'patient_id_number' => ['required', 'string', 'max:20'],
            'policy_number' => ['required', 'string', 'max:50'],
            'member_id' => ['nullable', 'string', 'max:50'],
            'service_date' => ['required', 'date'],
            'service_id' => ['nullable', 'uuid', 'exists:medical_services,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:doctors,id'],
            'diagnosis_code' => ['nullable', 'string', 'max:20'],
            'procedure_code' => ['nullable', 'string', 'max:20'],
            'claimed_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['status'] = 'draft';

        $claim = InsuranceClaim::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المطالبة بنجاح',
            'data' => $claim->load(['insuranceCompany', 'service']),
        ], 201);
    }

    /**
     * عرض مطالبة
     */
    public function show(InsuranceClaim $claim): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $claim->load(['insuranceCompany', 'service', 'doctor.employee', 'submittedBy', 'approvedBy']),
        ]);
    }

    /**
     * تحديث مطالبة
     */
    public function update(Request $request, InsuranceClaim $claim): JsonResponse
    {
        if (!in_array($claim->status, ['draft', 'submitted'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل مطالبة في هذه الحالة',
            ], 422);
        }

        $validated = $request->validate([
            'patient_name' => ['sometimes', 'string', 'max:200'],
            'policy_number' => ['sometimes', 'string', 'max:50'],
            'member_id' => ['nullable', 'string', 'max:50'],
            'service_date' => ['sometimes', 'date'],
            'service_id' => ['nullable', 'uuid', 'exists:medical_services,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:doctors,id'],
            'diagnosis_code' => ['nullable', 'string', 'max:20'],
            'procedure_code' => ['nullable', 'string', 'max:20'],
            'claimed_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $claim->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المطالبة بنجاح',
            'data' => $claim->fresh(['insuranceCompany', 'service']),
        ]);
    }

    /**
     * تدقيق المطالبة (Scrub)
     */
    public function scrub(InsuranceClaim $claim): JsonResponse
    {
        if (Gate::denies('finance.claims')) {
            abort(403, 'غير مصرح لك بتدقيق المطالبات');
        }

        $scrubResult = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // التحقق من البيانات الأساسية
        if (empty($claim->diagnosis_code)) {
            $scrubResult['warnings'][] = 'رمز التشخيص غير محدد';
        }

        if (empty($claim->procedure_code)) {
            $scrubResult['warnings'][] = 'رمز الإجراء غير محدد';
        }

        // التحقق من صلاحية التأمين
        $company = $claim->insuranceCompany;
        if ($company && !$company->is_contract_valid) {
            $scrubResult['errors'][] = 'عقد شركة التأمين منتهي';
            $scrubResult['is_valid'] = false;
        }

        $claim->update([
            'scrub_result' => $scrubResult,
            'scrub_notes' => $scrubResult['is_valid'] ? 'تم التدقيق بنجاح' : 'يوجد أخطاء في المطالبة',
            'status' => 'scrubbed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تدقيق المطالبة',
            'data' => [
                'claim' => $claim->fresh(),
                'scrub_result' => $scrubResult,
            ],
        ]);
    }

    /**
     * إرسال المطالبة
     */
    public function submit(InsuranceClaim $claim): JsonResponse
    {
        if (Gate::denies('finance.claims')) {
            abort(403, 'غير مصرح لك بإرسال المطالبات');
        }

        if (!in_array($claim->status, ['draft', 'scrubbed'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إرسال المطالبة في حالتها الحالية',
            ], 422);
        }

        $claim->update([
            'status' => 'submitted',
            'submission_date' => now(),
            'submitted_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال المطالبة بنجاح',
            'data' => $claim->fresh(),
        ]);
    }

    /**
     * الموافقة على المطالبة
     */
    public function approve(Request $request, InsuranceClaim $claim): JsonResponse
    {
        if (Gate::denies('finance.claims')) {
            abort(403, 'غير مصرح لك بالموافقة على المطالبات');
        }

        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0', 'max:' . $claim->claimed_amount],
            'deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'deduction_reason' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $status = $validated['approved_amount'] >= $claim->claimed_amount
            ? 'approved'
            : 'partially_approved';

        $claim->update([
            'approved_amount' => $validated['approved_amount'],
            'deduction_amount' => $validated['deduction_amount'] ?? 0,
            'deduction_reason' => $validated['deduction_reason'] ?? null,
            'status' => $status,
            'approval_date' => now(),
            'approved_by' => auth()->id(),
            'notes' => $validated['notes'] ?? $claim->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم الموافقة على المطالبة',
            'data' => $claim->fresh(),
        ]);
    }

    /**
     * رفض المطالبة
     */
    public function reject(Request $request, InsuranceClaim $claim): JsonResponse
    {
        if (Gate::denies('finance.claims')) {
            abort(403, 'غير مصرح لك برفض المطالبات');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by' => auth()->id(),
            'approval_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض المطالبة',
            'data' => $claim->fresh(),
        ]);
    }

    /**
     * تسجيل الدفع
     */
    public function markPaid(Request $request, InsuranceClaim $claim): JsonResponse
    {
        if (Gate::denies('finance.claims')) {
            abort(403, 'غير مصرح لك بتسجيل المدفوعات');
        }

        if (!in_array($claim->status, ['approved', 'partially_approved', 'partially_paid'])) {
            return response()->json([
                'success' => false,
                'message' => 'المطالبة غير موافق عليها',
            ], 422);
        }

        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $newPaidAmount = ($claim->paid_amount ?? 0) + $validated['paid_amount'];
        $status = $newPaidAmount >= $claim->approved_amount ? 'paid' : 'partially_paid';

        $claim->update([
            'paid_amount' => $newPaidAmount,
            'payment_date' => $validated['payment_date'] ?? now(),
            'status' => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدفع بنجاح',
            'data' => $claim->fresh(),
        ]);
    }
}
