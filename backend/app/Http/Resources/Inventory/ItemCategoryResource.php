<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'name' => app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar),
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn() => new ItemCategoryResource($this->parent)),
            'children' => ItemCategoryResource::collection($this->whenLoaded('children')),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'items_count' => $this->whenCounted('items'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
