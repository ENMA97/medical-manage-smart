<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'name' => $this->name ?? (app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar)),
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn() => new ItemCategoryResource($this->category)),
            'type' => $this->type,
            'type_name' => $this->type_name ?? null,

            // الوحدات
            'unit' => $this->unit,
            'secondary_unit' => $this->secondary_unit,
            'conversion_rate' => $this->conversion_rate,

            // للأدوية
            'generic_name' => $this->generic_name,
            'manufacturer' => $this->manufacturer,
            'strength' => $this->strength,
            'dosage_form' => $this->dosage_form,

            // المخزون
            'reorder_level' => $this->reorder_level,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'total_stock' => $this->total_stock ?? null,
            'is_low_stock' => $this->is_low_stock ?? null,
            'is_out_of_stock' => $this->is_out_of_stock ?? null,

            // الأسعار
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,

            // التتبع
            'track_batch' => $this->track_batch,
            'track_expiry' => $this->track_expiry,
            'is_controlled' => $this->is_controlled,
            'requires_prescription' => $this->requires_prescription,
            'is_active' => $this->is_active,

            // المخزون حسب المستودعات
            'stocks' => WarehouseStockResource::collection($this->whenLoaded('stocks')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
