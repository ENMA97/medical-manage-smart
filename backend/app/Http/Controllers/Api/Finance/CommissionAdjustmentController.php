<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CommissionAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommissionAdjustmentController extends Controller
{
    /**
     * قائمة تعديلات العمولات
     */
    public function index(Request $request): JsonResponse
    {
        $query = CommissionAdjustment::with(['doctor.employee', 'claim', 'requestedBy', 'approvedBy'])
            ->when($request->doctor_id, fn($q, $id) => $q->where('doctor_id', $id))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->date_from, fn($q, $date) => $q->where('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('created_at', '<=', $date . ' 23:59:59'))
            ->orderBy('created_at', 'desc');

        $adjustments = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $adjustments,
        ]);
    }

    /**
     * التعديلات المعلقة
     */
    public function pending(): JsonResponse
    {
        $adjustments = CommissionAdjustment::with(['doctor.employee', 'requestedBy'])
            ->pending()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $adjustments,
        ]);
    }

    /**
     * إنشاء تعديل عمولة
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('finance.commissions')) {
            abort(403, 'غير مصرح لك بإنشاء تعديلات العمولات');
        }

        $validated = $request->validate([
            'doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
            'type' => ['required', 'in:clawback,bonus,correction,penalty'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'claim_id' => ['nullable', 'uuid', 'exists:insurance_claims,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['status'] = 'pending';
        $validated['requested_by'] = auth()->id();

        $adjustment = CommissionAdjustment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء تعديل العمولة بنجاح',
            'data' => $adjustment->load(['doctor.employee']),
        ], 201);
    }

    /**
     * عرض تعديل عمولة
     */
    public function show(CommissionAdjustment $adjustment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $adjustment->load(['doctor.employee', 'claim', 'requestedBy', 'approvedBy']),
        ]);
    }

    /**
     * الموافقة على التعديل
     */
    public function approve(Request $request, CommissionAdjustment $adjustment): JsonResponse
    {
        if (Gate::denies('finance.commissions')) {
            abort(403, 'غير مصرح لك بالموافقة على تعديلات العمولات');
        }

        if ($adjustment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'التعديل ليس في حالة انتظار',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $adjustment->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $validated['notes'] ?? $adjustment->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم الموافقة على تعديل العمولة',
            'data' => $adjustment->fresh(['doctor.employee', 'approvedBy']),
        ]);
    }

    /**
     * رفض التعديل
     */
    public function reject(Request $request, CommissionAdjustment $adjustment): JsonResponse
    {
        if (Gate::denies('finance.commissions')) {
            abort(403, 'غير مصرح لك برفض تعديلات العمولات');
        }

        if ($adjustment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'التعديل ليس في حالة انتظار',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $adjustment->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => ($adjustment->notes ?? '') . "\nسبب الرفض: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض تعديل العمولة',
            'data' => $adjustment->fresh(),
        ]);
    }
}
