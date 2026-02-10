<?php

namespace App\Http\Resources\System;

use App\Http\Resources\HR\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'employee' => $this->whenLoaded('employee', fn() => new EmployeeResource($this->employee)),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => $this->when($this->relationLoaded('roles'), fn() => 
                $this->roles->flatMap->permissions->pluck('code')->unique()->values()
            ),
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'must_change_password' => $this->must_change_password,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
