<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
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
            'department' => $this->whenLoaded('department', fn() => new DepartmentResource($this->department)),
            'level' => $this->level,
            'min_salary' => $this->min_salary,
            'max_salary' => $this->max_salary,
            'is_medical' => $this->is_medical,
            'requires_license' => $this->requires_license,
            'is_active' => $this->is_active,
            'employees_count' => $this->whenCounted('employees'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
