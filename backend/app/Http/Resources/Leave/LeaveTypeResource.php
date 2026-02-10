<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'category' => $this->category,
            'default_days_per_year' => $this->default_days_per_year,
            'is_paid' => $this->is_paid,
            'requires_attachment' => $this->requires_attachment,
            'requires_hr_approval' => $this->requires_hr_approval,
            'requires_manager_approval' => $this->requires_manager_approval,
            'min_days' => $this->min_days,
            'max_days' => $this->max_days,
            'advance_notice_days' => $this->advance_notice_days,
            'can_be_carried_over' => $this->can_be_carried_over,
            'max_carry_over_days' => $this->max_carry_over_days,
            'applicable_contract_types' => $this->applicable_contract_types,
            'gender_restriction' => $this->gender_restriction,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
