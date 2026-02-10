<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryMovementRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InventoryMovementController extends Controller
{
    /**
     * قائمة الحركات
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['item', 'fromWarehouse', 'toWarehouse', 'performedBy'])
            ->when($request->item_id, fn($q, $id) => $q->where('item_id', $id))
            ->when($request->warehouse_id, function ($q, $id) {
                $q->where(function ($query) use ($id) {
                    $query->where('from_warehouse_id', $id)
                        ->orWhere('to_warehouse_id', $id);
                });
            })
            ->when($request->movement_type, fn($q, $type) => $q->where('movement_type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
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

    /**
     * استلام مخزون (إضافة)
     */
    public function receive(InventoryMovementRequest $request): JsonResponse
    {
        if (Gate::denies('inventory.receive')) {
            abort(403, 'غير مصرح لك باستلام المخزون');
        }

        $data = $request->validated();

        DB::beginTransaction();
        try {
            $movement = InventoryMovement::create([
                'movement_number' => InventoryMovement::generateMovementNumber('RCV'),
                'item_id' => $data['item_id'],
                'movement_type' => 'receive',
                'to_warehouse_id' => $data['warehouse_id'],
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => ($data['unit_cost'] ?? 0) * $data['quantity'],
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'movement_date' => now(),
                'performed_by' => auth()->id(),
                'status' => 'completed',
            ]);

            // تحديث المخزون
            $this->updateStock(
                $data['warehouse_id'],
                $data['item_id'],
                $data['quantity'],
                'add',
                $data['batch_number'] ?? null,
                $data['expiry_date'] ?? null,
                $data['unit_cost'] ?? null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم استلام المخزون بنجاح',
                'data' => $movement->load(['item', 'toWarehouse']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استلام المخزون: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * صرف مخزون
     */
    public function issue(InventoryMovementRequest $request): JsonResponse
    {
        if (Gate::denies('inventory.issue')) {
            abort(403, 'غير مصرح لك بصرف المخزون');
        }

        $data = $request->validated();

        // التحقق من توفر الكمية
        $availableQty = WarehouseStock::where('warehouse_id', $data['warehouse_id'])
            ->where('item_id', $data['item_id'])
            ->sum('quantity');

        if ($availableQty < $data['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'الكمية المطلوبة غير متوفرة. المتوفر: ' . $availableQty,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $movement = InventoryMovement::create([
                'movement_number' => InventoryMovement::generateMovementNumber('ISS'),
                'item_id' => $data['item_id'],
                'movement_type' => 'issue',
                'from_warehouse_id' => $data['warehouse_id'],
                'quantity' => $data['quantity'],
                'department_id' => $data['department_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'movement_date' => now(),
                'performed_by' => auth()->id(),
                'status' => 'completed',
            ]);

            // خصم المخزون (FEFO)
            $this->deductStockFEFO($data['warehouse_id'], $data['item_id'], $data['quantity']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم صرف المخزون بنجاح',
                'data' => $movement->load(['item', 'fromWarehouse']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء صرف المخزون: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحويل بين المستودعات
     */
    public function transfer(Request $request): JsonResponse
    {
        if (Gate::denies('inventory.transfer')) {
            abort(403, 'غير مصرح لك بتحويل المخزون');
        }

        $validated = $request->validate([
            'item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'from_warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'uuid', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // التحقق من توفر الكمية
        $availableQty = WarehouseStock::where('warehouse_id', $validated['from_warehouse_id'])
            ->where('item_id', $validated['item_id'])
            ->sum('quantity');

        if ($availableQty < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'الكمية المطلوبة غير متوفرة في المستودع المصدر',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $movement = InventoryMovement::create([
                'movement_number' => InventoryMovement::generateMovementNumber('TRF'),
                'item_id' => $validated['item_id'],
                'movement_type' => 'transfer',
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'quantity' => $validated['quantity'],
                'batch_number' => $validated['batch_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'movement_date' => now(),
                'performed_by' => auth()->id(),
                'status' => 'completed',
            ]);

            // خصم من المستودع المصدر
            $this->deductStockFEFO(
                $validated['from_warehouse_id'],
                $validated['item_id'],
                $validated['quantity']
            );

            // إضافة للمستودع الهدف
            $this->updateStock(
                $validated['to_warehouse_id'],
                $validated['item_id'],
                $validated['quantity'],
                'add'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم التحويل بنجاح',
                'data' => $movement->load(['item', 'fromWarehouse', 'toWarehouse']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحويل: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تسوية المخزون
     */
    public function adjust(Request $request): JsonResponse
    {
        if (Gate::denies('inventory.adjust')) {
            abort(403, 'غير مصرح لك بتسوية المخزون');
        }

        $validated = $request->validate([
            'item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $adjustmentType = $validated['quantity'] >= 0 ? 'adjustment_in' : 'adjustment_out';
        $quantity = abs($validated['quantity']);

        DB::beginTransaction();
        try {
            $movement = InventoryMovement::create([
                'movement_number' => InventoryMovement::generateMovementNumber('ADJ'),
                'item_id' => $validated['item_id'],
                'movement_type' => $adjustmentType,
                'from_warehouse_id' => $adjustmentType === 'adjustment_out' ? $validated['warehouse_id'] : null,
                'to_warehouse_id' => $adjustmentType === 'adjustment_in' ? $validated['warehouse_id'] : null,
                'quantity' => $quantity,
                'notes' => $validated['reason'],
                'movement_date' => now(),
                'performed_by' => auth()->id(),
                'status' => 'completed',
            ]);

            if ($adjustmentType === 'adjustment_in') {
                $this->updateStock($validated['warehouse_id'], $validated['item_id'], $quantity, 'add');
            } else {
                $this->updateStock($validated['warehouse_id'], $validated['item_id'], $quantity, 'subtract');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم التسوية بنجاح',
                'data' => $movement->load(['item']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التسوية: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إرجاع مخزون
     */
    public function returnItem(Request $request): JsonResponse
    {
        if (Gate::denies('inventory.return')) {
            abort(403, 'غير مصرح لك بإرجاع المخزون');
        }

        $validated = $request->validate([
            'item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'original_movement_id' => ['nullable', 'uuid', 'exists:inventory_movements,id'],
        ]);

        DB::beginTransaction();
        try {
            $movement = InventoryMovement::create([
                'movement_number' => InventoryMovement::generateMovementNumber('RET'),
                'item_id' => $validated['item_id'],
                'movement_type' => 'return',
                'to_warehouse_id' => $validated['warehouse_id'],
                'quantity' => $validated['quantity'],
                'reference_type' => 'inventory_movement',
                'reference_id' => $validated['original_movement_id'] ?? null,
                'notes' => $validated['reason'],
                'movement_date' => now(),
                'performed_by' => auth()->id(),
                'status' => 'completed',
            ]);

            $this->updateStock($validated['warehouse_id'], $validated['item_id'], $validated['quantity'], 'add');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم الإرجاع بنجاح',
                'data' => $movement->load(['item', 'toWarehouse']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإرجاع: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض حركة
     */
    public function show(InventoryMovement $movement): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $movement->load(['item.category', 'fromWarehouse', 'toWarehouse', 'performedBy', 'approvedBy']),
        ]);
    }

    /**
     * اعتماد حركة
     */
    public function approve(Request $request, InventoryMovement $movement): JsonResponse
    {
        if (Gate::denies('inventory.approve')) {
            abort(403, 'غير مصرح لك باعتماد الحركات');
        }

        if ($movement->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذه الحركة',
            ], 422);
        }

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['approved']) {
            $movement->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // تنفيذ الحركة على المخزون
            // ... يمكن إضافة منطق الموافقة هنا

            $message = 'تم اعتماد الحركة بنجاح';
        } else {
            $movement->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes' => $movement->notes . "\nسبب الرفض: " . ($validated['notes'] ?? 'غير محدد'),
            ]);

            $message = 'تم رفض الحركة';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $movement->fresh(),
        ]);
    }

    /**
     * تحديث المخزون
     */
    protected function updateStock(
        string $warehouseId,
        string $itemId,
        float $quantity,
        string $operation,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?float $unitCost = null
    ): void {
        $stock = WarehouseStock::firstOrNew([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'batch_number' => $batchNumber,
        ]);

        if ($operation === 'add') {
            $stock->quantity = ($stock->quantity ?? 0) + $quantity;
        } else {
            $stock->quantity = ($stock->quantity ?? 0) - $quantity;
        }

        if ($expiryDate) {
            $stock->expiry_date = $expiryDate;
        }

        if ($unitCost) {
            $stock->unit_cost = $unitCost;
        }

        $stock->save();
    }

    /**
     * خصم المخزون بطريقة FEFO (First Expire First Out)
     */
    protected function deductStockFEFO(string $warehouseId, string $itemId, float $quantity): void
    {
        $remainingQty = $quantity;

        $stocks = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('created_at')
            ->get();

        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $deduct = min($stock->quantity, $remainingQty);
            $stock->quantity -= $deduct;
            $stock->save();

            $remainingQty -= $deduct;
        }

        if ($remainingQty > 0) {
            throw new \Exception('الكمية المطلوبة غير متوفرة');
        }
    }
}
