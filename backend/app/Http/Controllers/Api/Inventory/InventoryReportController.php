<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    /**
     * ملخص المخزون
     */
    public function stockSummary(Request $request): JsonResponse
    {
        $query = WarehouseStock::with(['item.category', 'warehouse'])
            ->when($request->warehouse_id, fn($q, $id) => $q->where('warehouse_id', $id))
            ->when($request->category_id, function ($q, $catId) {
                $q->whereHas('item', fn($iq) => $iq->where('category_id', $catId));
            });

        // إجمالي الكميات
        $totalItems = (clone $query)->distinct('item_id')->count('item_id');
        $totalQuantity = (clone $query)->sum('quantity');
        $totalValue = (clone $query)->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')->value('value');

        // المخزون المنخفض
        $lowStockCount = (clone $query)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();

        // المنتهي الصلاحية
        $expiredCount = (clone $query)
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->count();

        // قريب الانتهاء (30 يوم)
        $expiringCount = (clone $query)
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->count();

        // توزيع حسب المستودعات
        $byWarehouse = WarehouseStock::with('warehouse')
            ->when($request->category_id, function ($q, $catId) {
                $q->whereHas('item', fn($iq) => $iq->where('category_id', $catId));
            })
            ->select(
                'warehouse_id',
                DB::raw('COUNT(DISTINCT item_id) as items_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(quantity * COALESCE(unit_cost, 0)) as total_value')
            )
            ->groupBy('warehouse_id')
            ->get()
            ->map(fn($row) => [
                'warehouse' => $row->warehouse,
                'items_count' => $row->items_count,
                'total_quantity' => $row->total_quantity,
                'total_value' => round($row->total_value, 2),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_items' => $totalItems,
                    'total_quantity' => $totalQuantity,
                    'total_value' => round($totalValue ?? 0, 2),
                    'low_stock_count' => $lowStockCount,
                    'expired_count' => $expiredCount,
                    'expiring_count' => $expiringCount,
                ],
                'by_warehouse' => $byWarehouse,
            ],
        ]);
    }

    /**
     * تقييم المخزون
     */
    public function valuation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'category_id' => ['nullable', 'uuid', 'exists:item_categories,id'],
            'as_of_date' => ['nullable', 'date'],
        ]);

        $query = WarehouseStock::with(['item.category', 'warehouse'])
            ->where('quantity', '>', 0)
            ->when($validated['warehouse_id'] ?? null, fn($q, $id) => $q->where('warehouse_id', $id))
            ->when($validated['category_id'] ?? null, function ($q, $catId) {
                $q->whereHas('item', fn($iq) => $iq->where('category_id', $catId));
            });

        $stocks = $query->get()->map(function ($stock) {
            return [
                'item' => $stock->item,
                'warehouse' => $stock->warehouse,
                'quantity' => $stock->quantity,
                'unit_cost' => $stock->unit_cost ?? 0,
                'total_value' => ($stock->quantity ?? 0) * ($stock->unit_cost ?? 0),
                'batch_number' => $stock->batch_number,
                'expiry_date' => $stock->expiry_date,
            ];
        });

        $totalValue = $stocks->sum('total_value');

        // حسب الفئة
        $byCategory = $stocks->groupBy(fn($s) => $s['item']->category_id ?? 'uncategorized')
            ->map(function ($items, $categoryId) {
                $firstItem = $items->first();
                return [
                    'category' => $firstItem['item']->category ?? null,
                    'items_count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'total_value' => round($items->sum('total_value'), 2),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_value' => round($totalValue, 2),
                'items_count' => $stocks->count(),
                'by_category' => $byCategory,
                'details' => $request->get('include_details') ? $stocks : null,
            ],
        ]);
    }

    /**
     * سجل الحركات
     */
    public function movementHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'item_id' => ['nullable', 'uuid', 'exists:inventory_items,id'],
            'movement_type' => ['nullable', 'in:receive,issue,transfer,adjustment_in,adjustment_out,return'],
        ]);

        $query = InventoryMovement::with(['item', 'fromWarehouse', 'toWarehouse', 'performedBy'])
            ->whereBetween('movement_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['warehouse_id'] ?? null, function ($q, $id) {
                $q->where(function ($query) use ($id) {
                    $query->where('from_warehouse_id', $id)
                        ->orWhere('to_warehouse_id', $id);
                });
            })
            ->when($validated['item_id'] ?? null, fn($q, $id) => $q->where('item_id', $id))
            ->when($validated['movement_type'] ?? null, fn($q, $type) => $q->where('movement_type', $type))
            ->orderBy('movement_date', 'desc');

        $movements = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        // ملخص حسب النوع
        $summaryByType = InventoryMovement::query()
            ->whereBetween('movement_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['warehouse_id'] ?? null, function ($q, $id) {
                $q->where(function ($query) use ($id) {
                    $query->where('from_warehouse_id', $id)
                        ->orWhere('to_warehouse_id', $id);
                });
            })
            ->select(
                'movement_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_cost) as total_value')
            )
            ->groupBy('movement_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'movements' => $movements,
                'summary_by_type' => $summaryByType,
            ],
        ]);
    }

    /**
     * تقرير انتهاء الصلاحية
     */
    public function expiryReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'days_ahead' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        $daysAhead = $validated['days_ahead'] ?? 90;

        $query = WarehouseStock::with(['item.category', 'warehouse'])
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->when($validated['warehouse_id'] ?? null, fn($q, $id) => $q->where('warehouse_id', $id));

        // المنتهية
        $expired = (clone $query)
            ->where('expiry_date', '<', now())
            ->orderBy('expiry_date')
            ->get();

        // ستنتهي خلال الفترة المحددة
        $expiringSoon = (clone $query)
            ->whereBetween('expiry_date', [now(), now()->addDays($daysAhead)])
            ->orderBy('expiry_date')
            ->get();

        // تجميع حسب الشهر
        $byMonth = (clone $query)
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addYear())
            ->get()
            ->groupBy(fn($stock) => $stock->expiry_date->format('Y-m'))
            ->map(fn($items, $month) => [
                'month' => $month,
                'count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
            ])
            ->sortKeys()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'expired' => [
                    'count' => $expired->count(),
                    'total_quantity' => $expired->sum('quantity'),
                    'items' => $expired,
                ],
                'expiring_soon' => [
                    'count' => $expiringSoon->count(),
                    'total_quantity' => $expiringSoon->sum('quantity'),
                    'items' => $expiringSoon,
                ],
                'by_month' => $byMonth,
            ],
        ]);
    }

    /**
     * تقرير الاستهلاك
     */
    public function consumption(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'category_id' => ['nullable', 'uuid', 'exists:item_categories,id'],
        ]);

        $query = InventoryMovement::query()
            ->whereIn('movement_type', ['issue', 'adjustment_out'])
            ->whereBetween('movement_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['warehouse_id'] ?? null, fn($q, $id) => $q->where('from_warehouse_id', $id))
            ->when($validated['department_id'] ?? null, fn($q, $id) => $q->where('department_id', $id))
            ->when($validated['category_id'] ?? null, function ($q, $catId) {
                $q->whereHas('item', fn($iq) => $iq->where('category_id', $catId));
            });

        // أكثر الأصناف استهلاكاً
        $topItems = InventoryMovement::with('item.category')
            ->whereIn('movement_type', ['issue', 'adjustment_out'])
            ->whereBetween('movement_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['warehouse_id'] ?? null, fn($q, $id) => $q->where('from_warehouse_id', $id))
            ->when($validated['department_id'] ?? null, fn($q, $id) => $q->where('department_id', $id))
            ->select(
                'item_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_cost) as total_value'),
                DB::raw('COUNT(*) as transactions_count')
            )
            ->groupBy('item_id')
            ->orderByDesc('total_quantity')
            ->limit(20)
            ->get()
            ->map(fn($row) => [
                'item' => $row->item,
                'total_quantity' => $row->total_quantity,
                'total_value' => round($row->total_value ?? 0, 2),
                'transactions_count' => $row->transactions_count,
            ]);

        // الاستهلاك اليومي
        $dailyConsumption = (clone $query)
            ->select(
                DB::raw('DATE(movement_date) as date'),
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(total_cost) as value')
            )
            ->groupBy(DB::raw('DATE(movement_date)'))
            ->orderBy('date')
            ->get();

        // الاستهلاك حسب القسم
        $byDepartment = (clone $query)
            ->whereNotNull('department_id')
            ->with('department')
            ->select(
                'department_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_cost) as total_value')
            )
            ->groupBy('department_id')
            ->orderByDesc('total_quantity')
            ->get()
            ->map(fn($row) => [
                'department' => $row->department,
                'total_quantity' => $row->total_quantity,
                'total_value' => round($row->total_value ?? 0, 2),
            ]);

        $totals = [
            'total_quantity' => (clone $query)->sum('quantity'),
            'total_value' => round((clone $query)->sum('total_cost') ?? 0, 2),
            'transactions_count' => (clone $query)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'top_items' => $topItems,
                'daily' => $dailyConsumption,
                'by_department' => $byDepartment,
            ],
        ]);
    }
}
