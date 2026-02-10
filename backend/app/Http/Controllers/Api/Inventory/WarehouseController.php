<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\WarehouseResource;
use App\Http\Resources\Inventory\WarehouseStockResource;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WarehouseController extends Controller
{
    /**
     * قائمة المستودعات
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Warehouse::with(['department', 'manager'])
            ->withCount('stocks')
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name_ar');

        $warehouses = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return WarehouseResource::collection($warehouses);
    }

    /**
     * المستودعات النشطة
     */
    public function active(): AnonymousResourceCollection
    {
        $warehouses = Warehouse::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return WarehouseResource::collection($warehouses);
    }

    /**
     * إنشاء مستودع جديد
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بإنشاء مستودعات');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:warehouses,code'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:main,sub,pharmacy,crash_cart,lab,radiology,department'],
            'location' => ['nullable', 'string', 'max:200'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'manager_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'track_batch' => ['sometimes', 'boolean'],
            'track_expiry' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $warehouse = Warehouse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المستودع بنجاح',
            'data' => new WarehouseResource($warehouse->load(['department', 'manager'])),
        ], 201);
    }

    /**
     * عرض مستودع
     */
    public function show(Warehouse $warehouse): WarehouseResource
    {
        return new WarehouseResource(
            $warehouse->load(['department', 'manager'])->loadCount('stocks')
        );
    }

    /**
     * تحديث مستودع
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بتعديل المستودعات');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:warehouses,code,' . $warehouse->id],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['sometimes', 'in:main,sub,pharmacy,crash_cart,lab,radiology,department'],
            'location' => ['nullable', 'string', 'max:200'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'manager_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'track_batch' => ['sometimes', 'boolean'],
            'track_expiry' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $warehouse->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستودع بنجاح',
            'data' => new WarehouseResource($warehouse->fresh(['department', 'manager'])),
        ]);
    }

    /**
     * حذف مستودع
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بحذف المستودعات');
        }

        // التحقق من عدم وجود مخزون
        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المستودع لوجود مخزون به',
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستودع بنجاح',
        ]);
    }

    /**
     * مخزون المستودع
     */
    public function stocks(Warehouse $warehouse, Request $request): AnonymousResourceCollection
    {
        $query = $warehouse->stocks()
            ->with(['item.category'])
            ->when($request->category_id, function ($q, $catId) {
                $q->whereHas('item', fn($iq) => $iq->where('category_id', $catId));
            })
            ->when($request->low_stock, fn($q) => $q->where('quantity', '<=', 'reorder_level'))
            ->when($request->search, function ($q, $search) {
                $q->whereHas('item', function ($iq) use ($search) {
                    $iq->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('updated_at', 'desc');

        $stocks = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return WarehouseStockResource::collection($stocks);
    }

    /**
     * حركات المستودع
     */
    public function movements(Warehouse $warehouse, Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['item', 'performedBy'])
            ->where(function ($q) use ($warehouse) {
                $q->where('from_warehouse_id', $warehouse->id)
                    ->orWhere('to_warehouse_id', $warehouse->id);
            })
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
