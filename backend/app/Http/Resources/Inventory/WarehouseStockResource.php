<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => $this->whenLoaded('warehouse', fn() => new WarehouseResource($this->warehouse)),
            'item' => $this->whenLoaded('item', fn() => new InventoryItemResource($this->item)),
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'is_expired' => $this->expiry_date ? $this->expiry_date->isPast() : false,
            'days_to_expiry' => $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'cost_price' => $this->cost_price,
            'total_value' => $this->total_value ?? ($this->quantity * ($this->cost_price ?? 0)),
            'location_in_warehouse' => $this->location_in_warehouse,
            'version' => $this->version,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
