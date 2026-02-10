<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision_number' => $this->decision_number,
            'leave_request_id' => $this->leave_request_id,
            'employee_type' => $this->employee_type,
            'status' => $this->status,
            'requires_gm_approval' => $this->requires_gm_approval,
            'forwarded_to_gm' => $this->forwarded_to_gm,
            'forward_reason' => $this->forward_reason,

            // المدير الإداري
            'admin_manager_action' => $this->admin_manager_action,
            'admin_manager_action_at' => $this->admin_manager_action_at?->toISOString(),
            'admin_manager_comment' => $this->admin_manager_comment,

            // المدير الطبي
            'medical_director_action' => $this->medical_director_action,
            'medical_director_action_at' => $this->medical_director_action_at?->toISOString(),
            'medical_director_comment' => $this->medical_director_comment,

            // المدير العام
            'general_manager_action' => $this->general_manager_action,
            'general_manager_action_at' => $this->general_manager_action_at?->toISOString(),
            'general_manager_comment' => $this->general_manager_comment,

            'final_approved_at' => $this->final_approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
