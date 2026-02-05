<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseStock;
use App\Models\Inventory\ItemQuota;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    /**
     * استلام بضاعة
     * FEFO: First Expire First Out
     */
    public function receive(
        string $itemId,
        string $warehouseId,
        float $quantity,
        string $createdBy,
        ?string $batchNumber = null,
        ?string $lotNumber = null,
        ?\DateTime $expiryDate = null,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $warehouseId, $quantity, $createdBy,
            $batchNumber, $lotNumber, $expiryDate, $unitCost,
            $referenceType, $referenceId, $notes
        ) {
            $item = InventoryItem::findOrFail($itemId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            // إنشاء أو تحديث الرصيد
            $stock = WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                ],
                [
                    'quantity' => 0,
                    'lot_number' => $lotNumber,
                    'unit_cost' => $unitCost ?? $item->unit_cost,
                    'version' => 1,
                ]
            );

            $stock->addQuantity($quantity);

            // إنشاء حركة المخزون
            return InventoryMovement::create([
                'type' => InventoryMovement::TYPE_RECEIVE,
                'status' => InventoryMovement::STATUS_COMPLETED,
                'item_id' => $itemId,
                'to_warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost ?? $item->unit_cost,
                'batch_number' => $batchNumber,
                'lot_number' => $lotNumber,
                'expiry_date' => $expiryDate,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * صرف بضاعة بنظام FEFO
     */
    public function issue(
        string $itemId,
        string $warehouseId,
        float $quantity,
        string $createdBy,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $warehouseId, $quantity, $createdBy,
            $referenceType, $referenceId, $reason, $notes
        ) {
            $warehouse = Warehouse::findOrFail($warehouseId);

            // التحقق من الكمية المتاحة
            $totalAvailable = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->sum('quantity');

            if ($totalAvailable < $quantity && !$warehouse->allows_negative_stock) {
                throw new Exception("الكمية المتاحة ({$totalAvailable}) أقل من المطلوبة ({$quantity})");
            }

            // صرف بنظام FEFO
            $remainingQty = $quantity;
            $totalCost = 0;
            $stocks = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->where('quantity', '>', 0)
                ->byFefo()
                ->get();

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                $qtyToDeduct = min($remainingQty, $stock->available_quantity);
                $stock->subtractQuantity($qtyToDeduct);
                $totalCost += $qtyToDeduct * ($stock->unit_cost ?? 0);
                $remainingQty -= $qtyToDeduct;
            }

            // إنشاء حركة الصرف
            return InventoryMovement::create([
                'type' => InventoryMovement::TYPE_ISSUE,
                'status' => InventoryMovement::STATUS_COMPLETED,
                'item_id' => $itemId,
                'from_warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'unit_cost' => $totalCost / $quantity,
                'total_cost' => $totalCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * تحويل بين المستودعات
     */
    public function transfer(
        string $itemId,
        string $fromWarehouseId,
        string $toWarehouseId,
        float $quantity,
        string $createdBy,
        ?string $batchNumber = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $fromWarehouseId, $toWarehouseId, $quantity,
            $createdBy, $batchNumber, $notes
        ) {
            // صرف من المستودع المصدر
            $this->issue($itemId, $fromWarehouseId, $quantity, $createdBy, 'transfer', null, 'تحويل لمستودع آخر');

            // استلام في المستودع المستهدف
            $sourceStock = WarehouseStock::where('warehouse_id', $fromWarehouseId)
                ->where('item_id', $itemId)
                ->when($batchNumber, fn($q) => $q->where('batch_number', $batchNumber))
                ->first();

            $this->receive(
                $itemId,
                $toWarehouseId,
                $quantity,
                $createdBy,
                $sourceStock?->batch_number,
                $sourceStock?->lot_number,
                $sourceStock?->expiry_date,
                $sourceStock?->unit_cost,
                'transfer',
                null,
                $notes
            );

            // إنشاء حركة التحويل
            return InventoryMovement::create([
                'type' => InventoryMovement::TYPE_TRANSFER,
                'status' => InventoryMovement::STATUS_COMPLETED,
                'item_id' => $itemId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'quantity' => $quantity,
                'batch_number' => $batchNumber,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * تسوية المخزون
     */
    public function adjust(
        string $itemId,
        string $warehouseId,
        float $newQuantity,
        string $createdBy,
        string $reason,
        ?string $batchNumber = null,
        ?string $notes = null
    ): InventoryMovement {
        return DB::transaction(function () use (
            $itemId, $warehouseId, $newQuantity, $createdBy, $reason, $batchNumber, $notes
        ) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->when($batchNumber, fn($q) => $q->where('batch_number', $batchNumber))
                ->first();

            $currentQty = $stock?->quantity ?? 0;
            $difference = $newQuantity - $currentQty;

            if ($stock) {
                $stock->quantity = $newQuantity;
                $stock->version++;
                $stock->save();
            } else {
                WarehouseStock::create([
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId,
                    'quantity' => $newQuantity,
                    'batch_number' => $batchNumber,
                    'version' => 1,
                ]);
            }

            return InventoryMovement::create([
                'type' => InventoryMovement::TYPE_ADJUSTMENT,
                'status' => InventoryMovement::STATUS_COMPLETED,
                'item_id' => $itemId,
                'from_warehouse_id' => $difference < 0 ? $warehouseId : null,
                'to_warehouse_id' => $difference > 0 ? $warehouseId : null,
                'quantity' => abs($difference),
                'batch_number' => $batchNumber,
                'reason' => $reason,
                'notes' => "تسوية من {$currentQty} إلى {$newQuantity}. " . ($notes ?? ''),
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * التحقق من الحصة (Quota)
     */
    public function checkQuota(
        string $itemId,
        string $departmentId,
        float $quantity
    ): array {
        $quota = ItemQuota::where('item_id', $itemId)
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->first();

        if (!$quota) {
            return ['allowed' => true, 'message' => 'لا توجد حصة محددة'];
        }

        $consumed = $quota->getConsumedInPeriod();
        $remaining = $quota->quota_amount - $consumed;

        if ($quantity > $remaining) {
            return [
                'allowed' => false,
                'message' => "تجاوز الحصة المسموحة. المتبقي: {$remaining}",
                'remaining' => $remaining,
                'consumed' => $consumed,
                'limit' => $quota->quota_amount,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining - $quantity,
            'consumed' => $consumed + $quantity,
            'limit' => $quota->quota_amount,
        ];
    }

    /**
     * الأصناف منتهية الصلاحية
     */
    public function getExpiredItems(?string $warehouseId = null)
    {
        $query = WarehouseStock::expired()->withStock()->with(['item', 'warehouse']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * الأصناف قريبة من الانتهاء
     */
    public function getExpiringItems(int $days = 30, ?string $warehouseId = null)
    {
        $query = WarehouseStock::expiringSoon($days)->withStock()->with(['item', 'warehouse']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * الأصناف تحت حد الطلب
     */
    public function getLowStockItems(?string $warehouseId = null)
    {
        return InventoryItem::active()
            ->whereHas('stocks', function ($query) use ($warehouseId) {
                $query->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));
            })
            ->get()
            ->filter(fn($item) => $item->is_low_stock);
    }

    /**
     * إحصائيات المخزون
     */
    public function getStatistics(?string $warehouseId = null): array
    {
        $stockQuery = WarehouseStock::query();
        if ($warehouseId) {
            $stockQuery->where('warehouse_id', $warehouseId);
        }

        return [
            'total_items' => InventoryItem::active()->count(),
            'total_stock_value' => $stockQuery->sum(DB::raw('quantity * COALESCE(unit_cost, 0)')),
            'expired_items_count' => $this->getExpiredItems($warehouseId)->count(),
            'expiring_items_count' => $this->getExpiringItems(30, $warehouseId)->count(),
            'low_stock_items_count' => $this->getLowStockItems($warehouseId)->count(),
            'out_of_stock_count' => InventoryItem::active()->get()->filter(fn($i) => $i->is_out_of_stock)->count(),
            'movements_today' => InventoryMovement::whereDate('created_at', today())->count(),
        ];
    }
}
