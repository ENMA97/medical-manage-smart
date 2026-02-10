<?php

namespace App\Http\Resources\Inventory;

use App\Http\Resources\HR\DepartmentResource;
use App\Http\Resources\HR\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'name' => app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar),
            'description' => $this->description,
            'type' => $this->type,
            'type_name' => $this->type_name ?? null,
            'location' => $this->location,
            'department' => $this->whenLoaded('department', fn() => new DepartmentResource($this->department)),
            'manager' => $this->whenLoaded('manager', fn() => new EmployeeResource($this->manager)),
            'is_active' => $this->is_active,
            'requires_approval' => $this->requires_approval,
            'track_batch' => $this->track_batch,
            'track_expiry' => $this->track_expiry,
            'stocks_count' => $this->whenCounted('stocks'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
