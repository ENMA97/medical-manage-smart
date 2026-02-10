<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'approver_type' => $this->approver_type,
            'action_type' => $this->action_type,
            'approver_id' => $this->approver_id,
            'approver_name' => $this->whenLoaded('approver', fn() => $this->approver->name),
            'status' => $this->status,
            'comment' => $this->comment,
            'comment_ar' => $this->comment_ar,
            'job_tasks_assigned' => $this->job_tasks_assigned,
            'action_at' => $this->action_at?->toISOString(),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
