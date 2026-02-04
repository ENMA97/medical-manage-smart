<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceAdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leave_balance_id' => $this->leave_balance_id,
            'leave_request_id' => $this->leave_request_id,
            'adjustment_type' => $this->adjustment_type,
            'adjustment_type_name' => $this->adjustment_type_name,
            'days_amount' => (float) $this->days_amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'reason' => $this->reason,
            'is_addition' => $this->isAddition(),
            'is_deduction' => $this->isDeduction(),
            'performed_by' => $this->performed_by,
            'performed_by_user' => $this->whenLoaded('performedByUser', fn() => [
                'id' => $this->performedByUser->id,
                'name' => $this->performedByUser->name,
            ]),
            'leave_request' => $this->whenLoaded('leaveRequest', fn() => [
                'id' => $this->leaveRequest->id,
                'request_number' => $this->leaveRequest->request_number,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
