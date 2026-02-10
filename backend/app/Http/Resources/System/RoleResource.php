<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'is_system' => $this->is_system,
            'is_active' => $this->is_active,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
