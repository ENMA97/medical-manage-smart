<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ItemQuota;
use App\Models\Inventory\QuotaConsumption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItemQuotaController extends Controller
{
    /**
     * قائمة الحصص
     */
    public function index(Request $request): JsonResponse
    {
        $query = ItemQuota::with(['item', 'department'])
            ->when($request->item_id, fn($q, $id) => $q->where('item_id', $id))
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->period_type, fn($q, $type) => $q->where('period_type', $type))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('created_at', 'desc');

        $quotas = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $quotas,
        ]);
    }

    /**
     * حصص قسم معين
     */
    public function byDepartment(string $departmentId): JsonResponse
    {
        $quotas = ItemQuota::with(['item'])
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->get()
            ->map(function ($quota) {
                $consumption = $this->calculateCurrentConsumption($quota);
                return [
                    'quota' => $quota,
                    'consumed' => $consumption['consumed'],
                    'remaining' => $consumption['remaining'],
                    'percentage' => $consumption['percentage'],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $quotas,
        ]);
    }

    /**
     * إنشاء حصة جديدة
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بإنشاء حصص');
        }

        $validated = $request->validate([
            'item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'quota_limit' => ['required', 'numeric', 'min:0'],
            'period_type' => ['required', 'in:daily,weekly,monthly'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // التحقق من عدم وجود حصة مكررة
        $exists = ItemQuota::where('item_id', $validated['item_id'])
            ->where('department_id', $validated['department_id'])
            ->where('period_type', $validated['period_type'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد حصة مسبقة لهذا الصنف والقسم والفترة',
            ], 422);
        }

        $quota = ItemQuota::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحصة بنجاح',
            'data' => $quota->load(['item', 'department']),
        ], 201);
    }

    /**
     * عرض حصة
     */
    public function show(ItemQuota $quota): JsonResponse
    {
        $consumption = $this->calculateCurrentConsumption($quota);

        return response()->json([
            'success' => true,
            'data' => [
                'quota' => $quota->load(['item', 'department']),
                'consumed' => $consumption['consumed'],
                'remaining' => $consumption['remaining'],
                'percentage' => $consumption['percentage'],
            ],
        ]);
    }

    /**
     * تحديث حصة
     */
    public function update(Request $request, ItemQuota $quota): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بتعديل الحصص');
        }

        $validated = $request->validate([
            'quota_limit' => ['sometimes', 'numeric', 'min:0'],
            'period_type' => ['sometimes', 'in:daily,weekly,monthly'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $quota->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحصة بنجاح',
            'data' => $quota->fresh(['item', 'department']),
        ]);
    }

    /**
     * حذف حصة
     */
    public function destroy(ItemQuota $quota): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بحذف الحصص');
        }

        $quota->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحصة بنجاح',
        ]);
    }

    /**
     * سجل استهلاك الحصة
     */
    public function consumption(ItemQuota $quota, Request $request): JsonResponse
    {
        $query = QuotaConsumption::with(['employee'])
            ->where('quota_id', $quota->id)
            ->when($request->date_from, fn($q, $date) => $q->where('consumed_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('consumed_at', '<=', $date . ' 23:59:59'))
            ->orderBy('consumed_at', 'desc');

        $consumptions = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $consumptions,
        ]);
    }

    /**
     * حساب الاستهلاك الحالي
     */
    protected function calculateCurrentConsumption(ItemQuota $quota): array
    {
        $periodStart = match ($quota->period_type) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $consumed = QuotaConsumption::where('quota_id', $quota->id)
            ->where('consumed_at', '>=', $periodStart)
            ->sum('quantity');

        $remaining = max(0, $quota->quota_limit - $consumed);
        $percentage = $quota->quota_limit > 0
            ? round(($consumed / $quota->quota_limit) * 100, 2)
            : 0;

        return [
            'consumed' => $consumed,
            'remaining' => $remaining,
            'percentage' => $percentage,
        ];
    }
}
