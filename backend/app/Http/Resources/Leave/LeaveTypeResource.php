<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'category' => $this->category,
            'category_name' => $this->category_name,
            'default_days_per_year' => $this->default_days_per_year,
            'max_consecutive_days' => $this->max_consecutive_days,
            'min_request_days' => $this->min_request_days,
            'max_carry_over_days' => $this->max_carry_over_days,
            'advance_notice_days' => $this->advance_notice_days,
            'is_paid' => $this->is_paid,
            'deduct_from_salary' => $this->deduct_from_salary,
            'requires_attachment' => $this->requires_attachment,
            'requires_delegate' => $this->requires_delegate,
            'can_be_carried_over' => $this->can_be_carried_over,
            'applicable_gender' => $this->applicable_gender,
            'requires_medical_certificate' => $this->requires_medical_certificate,
            'allow_partial_days' => $this->allow_partial_days,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
