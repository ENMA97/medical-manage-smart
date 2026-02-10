<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\WarehouseStockResource;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class InventoryItemController extends Controller
{
    /**
     * قائمة الأصناف
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = InventoryItem::with(['category'])
            ->when($request->category_id, fn($q, $id) => $q->where('category_id', $id))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->is_controlled !== null, fn($q) => $q->where('is_controlled', $request->boolean('is_controlled')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort_by ?? 'name_ar', $request->sort_dir ?? 'asc');

        $items = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return InventoryItemResource::collection($items);
    }

    /**
     * البحث السريع عن صنف
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $query = $request->get('q', '');

        $items = InventoryItem::with(['category'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%")
                    ->orWhere('name_ar', 'like', "%{$query}%")
                    ->orWhere('name_en', 'like', "%{$query}%")
                    ->orWhere('generic_name', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return InventoryItemResource::collection($items);
    }

    /**
     * الأصناف ذات المخزون المنخفض
     */
    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $items = InventoryItem::with(['category', 'stocks.warehouse'])
            ->where('is_active', true)
            ->whereHas('stocks', function ($q) {
                $q->whereRaw('quantity <= reorder_level');
            })
            ->when($request->warehouse_id, function ($q, $warehouseId) {
                $q->whereHas('stocks', fn($sq) => $sq->where('warehouse_id', $warehouseId));
            })
            ->orderBy('name_ar')
            ->get();

        return InventoryItemResource::collection($items);
    }

    /**
     * الأصناف قريبة الانتهاء
     */
    public function expiring(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $expiringStocks = \App\Models\Inventory\WarehouseStock::with(['item.category', 'warehouse'])
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->orderBy('expiry_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $expiringStocks,
        ]);
    }

    /**
     * إنشاء صنف جديد
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بإنشاء أصناف');
        }

        $item = InventoryItem::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الصنف بنجاح',
            'data' => new InventoryItemResource($item->load('category')),
        ], 201);
    }

    /**
     * عرض صنف
     */
    public function show(InventoryItem $item): InventoryItemResource
    {
        return new InventoryItemResource(
            $item->load(['category', 'stocks.warehouse'])
        );
    }

    /**
     * تحديث صنف
     */
    public function update(Request $request, InventoryItem $item): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بتعديل الأصناف');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:inventory_items,code,' . $item->id],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:inventory_items,barcode,' . $item->id],
            'name_ar' => ['sometimes', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['sometimes', 'uuid', 'exists:item_categories,id'],
            'type' => ['sometimes', 'in:medicine,consumable,equipment,tool,other'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'secondary_unit' => ['nullable', 'string', 'max:50'],
            'conversion_rate' => ['nullable', 'numeric', 'min:0'],
            'generic_name' => ['nullable', 'string', 'max:200'],
            'manufacturer' => ['nullable', 'string', 'max:200'],
            'strength' => ['nullable', 'string', 'max:100'],
            'dosage_form' => ['nullable', 'string', 'max:100'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'track_batch' => ['sometimes', 'boolean'],
            'track_expiry' => ['sometimes', 'boolean'],
            'is_controlled' => ['sometimes', 'boolean'],
            'requires_prescription' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الصنف بنجاح',
            'data' => new InventoryItemResource($item->fresh('category')),
        ]);
    }

    /**
     * حذف صنف
     */
    public function destroy(InventoryItem $item): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بحذف الأصناف');
        }

        // التحقق من عدم وجود مخزون
        if ($item->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الصنف لوجود مخزون متوفر',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الصنف بنجاح',
        ]);
    }

    /**
     * مخزون الصنف في المستودعات
     */
    public function stocks(InventoryItem $item): AnonymousResourceCollection
    {
        $stocks = $item->stocks()
            ->with(['warehouse'])
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get();

        return WarehouseStockResource::collection($stocks);
    }

    /**
     * حركات الصنف
     */
    public function movements(InventoryItem $item, Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['fromWarehouse', 'toWarehouse', 'performedBy'])
            ->where('item_id', $item->id)
            ->when($request->type, fn($q, $type) => $q->where('movement_type', $type))
            ->when($request->date_from, fn($q, $date) => $q->where('movement_date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('movement_date', '<=', $date))
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc');

        $movements = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }
}
