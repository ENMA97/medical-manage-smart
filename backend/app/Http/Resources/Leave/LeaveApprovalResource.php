<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leave_request_id' => $this->leave_request_id,
            'approver_id' => $this->approver_id,
            'approver' => $this->whenLoaded('approver', fn() => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),
            'approval_level' => $this->approval_level,
            'approval_level_name' => $this->approval_level_name,
            'action_type' => $this->action_type,
            'action_type_name' => $this->action_type_name,
            'status' => $this->status,
            'status_name' => $this->getStatusName(),
            'comment' => $this->comment,
            'job_tasks' => $this->job_tasks,
            'ip_address' => $this->ip_address,
            'acted_at' => $this->acted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get status name in Arabic
     */
    private function getStatusName(): string
    {
        return match ($this->status) {
            'pending' => 'معلق',
            'approved' => 'موافق',
            'rejected' => 'مرفوض',
            default => $this->status,
        };
    }
}
