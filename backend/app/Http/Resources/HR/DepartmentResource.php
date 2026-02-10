<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'name' => app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar),
            'description_ar' => $this->description_ar,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn() => new DepartmentResource($this->parent)),
            'children' => DepartmentResource::collection($this->whenLoaded('children')),
            'manager' => $this->whenLoaded('manager', fn() => new EmployeeResource($this->manager)),
            'cost_center_code' => $this->cost_center_code,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'employees_count' => $this->whenCounted('employees'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
