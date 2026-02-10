<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\System\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PurchaseRequestController extends Controller
{
    /**
     * قائمة طلبات الشراء
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseRequest::with(['requestedBy', 'department', 'items.item'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->priority, fn($q, $priority) => $q->where('priority', $priority))
            ->when($request->date_from, fn($q, $date) => $q->where('request_date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('request_date', '<=', $date))
            ->when($request->search, function ($q, $search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc');

        $requests = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * الطلبات المعلقة
     */
    public function pending(): JsonResponse
    {
        $requests = PurchaseRequest::with(['requestedBy', 'department'])
            ->whereIn('status', ['pending', 'pending_manager', 'pending_finance', 'pending_ceo'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * إنشاء طلب شراء
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'purpose' => ['required', 'string', 'max:500'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'needed_by' => ['nullable', 'date', 'after:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();
        try {
            $purchaseRequest = PurchaseRequest::create([
                'request_number' => PurchaseRequest::generateRequestNumber(),
                'department_id' => $validated['department_id'],
                'requested_by' => auth()->id(),
                'purpose' => $validated['purpose'],
                'priority' => $validated['priority'] ?? 'medium',
                'needed_by' => $validated['needed_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'request_date' => now(),
            ]);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['estimated_unit_price'] ?? 0) * $item['quantity'];
                $purchaseRequest->items()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'estimated_unit_price' => $item['estimated_unit_price'] ?? null,
                    'total_price' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
                $totalAmount += $lineTotal;
            }

            $purchaseRequest->update(['total_estimated_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء طلب الشراء بنجاح',
                'data' => $purchaseRequest->load(['items.item', 'department']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب',
            ], 500);
        }
    }

    /**
     * عرض طلب شراء
     */
    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $purchaseRequest->load([
                'items.item',
                'department',
                'requestedBy',
                'managerApprovedBy',
                'financeApprovedBy',
                'ceoApprovedBy',
            ]),
        ]);
    }

    /**
     * تحديث طلب شراء
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل طلب غير مسودة',
            ], 422);
        }

        $validated = $request->validate([
            'purpose' => ['sometimes', 'string', 'max:500'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'needed_by' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الطلب بنجاح',
            'data' => $purchaseRequest->fresh(['items.item']),
        ]);
    }

    /**
     * إرسال الطلب للموافقة
     */
    public function submit(PurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إرسال هذا الطلب',
            ], 422);
        }

        $purchaseRequest->update([
            'status' => 'pending_manager',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الطلب للموافقة',
            'data' => $purchaseRequest->fresh(),
        ]);
    }

    /**
     * موافقة المدير
     */
    public function managerApprove(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if (Gate::denies('purchase.manager_approve')) {
            abort(403, 'غير مصرح لك بالموافقة');
        }

        if ($purchaseRequest->status !== 'pending_manager') {
            return response()->json([
                'success' => false,
                'message' => 'الطلب ليس في مرحلة موافقة المدير',
            ], 422);
        }

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['approved']) {
            $purchaseRequest->update([
                'status' => 'pending_finance',
                'manager_approved_by' => auth()->id(),
                'manager_approved_at' => now(),
                'manager_notes' => $validated['notes'] ?? null,
            ]);
            $message = 'تم موافقة المدير وتحويل الطلب للمالية';
        } else {
            $purchaseRequest->update([
                'status' => 'rejected',
                'manager_approved_by' => auth()->id(),
                'manager_approved_at' => now(),
                'manager_notes' => $validated['notes'] ?? null,
                'rejection_reason' => $validated['notes'] ?? 'مرفوض من المدير',
            ]);
            $message = 'تم رفض الطلب';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $purchaseRequest->fresh(),
        ]);
    }

    /**
     * موافقة المالية
     */
    public function financeApprove(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if (Gate::denies('purchase.finance_approve')) {
            abort(403, 'غير مصرح لك بالموافقة');
        }

        if ($purchaseRequest->status !== 'pending_finance') {
            return response()->json([
                'success' => false,
                'message' => 'الطلب ليس في مرحلة موافقة المالية',
            ], 422);
        }

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['approved']) {
            // إذا كان المبلغ كبيراً، يحتاج موافقة المدير التنفيذي
            $ceoThreshold = config('app.purchase_ceo_threshold', 50000);
            $nextStatus = $purchaseRequest->total_estimated_amount >= $ceoThreshold
                ? 'pending_ceo'
                : 'approved';

            $purchaseRequest->update([
                'status' => $nextStatus,
                'finance_approved_by' => auth()->id(),
                'finance_approved_at' => now(),
                'finance_notes' => $validated['notes'] ?? null,
            ]);

            $message = $nextStatus === 'approved'
                ? 'تم اعتماد الطلب'
                : 'تم موافقة المالية وتحويل الطلب للمدير التنفيذي';
        } else {
            $purchaseRequest->update([
                'status' => 'rejected',
                'finance_approved_by' => auth()->id(),
                'finance_approved_at' => now(),
                'finance_notes' => $validated['notes'] ?? null,
                'rejection_reason' => $validated['notes'] ?? 'مرفوض من المالية',
            ]);
            $message = 'تم رفض الطلب';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $purchaseRequest->fresh(),
        ]);
    }

    /**
     * موافقة المدير التنفيذي
     */
    public function ceoApprove(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if (Gate::denies('purchase.ceo_approve')) {
            abort(403, 'غير مصرح لك بالموافقة');
        }

        if ($purchaseRequest->status !== 'pending_ceo') {
            return response()->json([
                'success' => false,
                'message' => 'الطلب ليس في مرحلة موافقة المدير التنفيذي',
            ], 422);
        }

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['approved']) {
            $purchaseRequest->update([
                'status' => 'approved',
                'ceo_approved_by' => auth()->id(),
                'ceo_approved_at' => now(),
                'ceo_notes' => $validated['notes'] ?? null,
            ]);
            $message = 'تم اعتماد الطلب';
        } else {
            $purchaseRequest->update([
                'status' => 'rejected',
                'ceo_approved_by' => auth()->id(),
                'ceo_approved_at' => now(),
                'ceo_notes' => $validated['notes'] ?? null,
                'rejection_reason' => $validated['notes'] ?? 'مرفوض من المدير التنفيذي',
            ]);
            $message = 'تم رفض الطلب';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $purchaseRequest->fresh(),
        ]);
    }

    /**
     * رفض الطلب
     */
    public function reject(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if (!in_array($purchaseRequest->status, ['pending_manager', 'pending_finance', 'pending_ceo'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض هذا الطلب',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $purchaseRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب',
        ]);
    }

    /**
     * استلام المشتريات
     */
    public function receive(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if (Gate::denies('inventory.receive')) {
            abort(403, 'غير مصرح لك باستلام المشتريات');
        }

        if ($purchaseRequest->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير معتمد',
            ], 422);
        }

        $validated = $request->validate([
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'items' => ['required', 'array'],
            'items.*.item_id' => ['required', 'uuid'],
            'items.*.received_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        // يمكن إضافة منطق استلام المخزون هنا
        // وربطه مع InventoryMovementController

        $purchaseRequest->update([
            'status' => 'completed',
            'received_at' => now(),
            'received_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام المشتريات بنجاح',
        ]);
    }
}
