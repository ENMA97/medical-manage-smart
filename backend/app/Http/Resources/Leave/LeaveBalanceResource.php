<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'name' => $this->employee->name ?? $this->employee->full_name,
                'employee_number' => $this->employee->employee_number ?? null,
            ]),
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'year' => $this->year,
            'entitled_days' => (float) $this->entitled_days,
            'carried_over_days' => (float) $this->carried_over_days,
            'additional_days' => (float) $this->additional_days,
            'total_available' => (float) $this->total_available,
            'used_days' => (float) $this->used_days,
            'pending_days' => (float) $this->pending_days,
            'remaining_days' => (float) $this->remaining_days,
            'utilization_percentage' => $this->total_available > 0
                ? round(($this->used_days / $this->total_available) * 100, 2)
                : 0,
            'adjustments' => LeaveBalanceAdjustmentResource::collection($this->whenLoaded('adjustments')),
            'last_updated_by' => $this->last_updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
