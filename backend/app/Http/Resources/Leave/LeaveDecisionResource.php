<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveDecisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision_number' => $this->decision_number,
            'leave_request_id' => $this->leave_request_id,
            'leave_request' => $this->whenLoaded('leaveRequest', fn() => [
                'id' => $this->leaveRequest->id,
                'request_number' => $this->leaveRequest->request_number,
                'employee' => $this->leaveRequest->employee ? [
                    'id' => $this->leaveRequest->employee->id,
                    'name' => $this->leaveRequest->employee->name ?? $this->leaveRequest->employee->full_name,
                ] : null,
                'leave_type' => $this->leaveRequest->leaveType ? [
                    'id' => $this->leaveRequest->leaveType->id,
                    'name_ar' => $this->leaveRequest->leaveType->name_ar,
                ] : null,
                'start_date' => $this->leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $this->leaveRequest->end_date?->format('Y-m-d'),
                'working_days' => $this->leaveRequest->working_days,
            ]),
            'status' => $this->status,
            'status_name' => $this->status_name,
            'status_color' => $this->getStatusColor(),
            'requires_gm_approval' => $this->requires_gm_approval,
            'forwarded_to_gm' => $this->forwarded_to_gm,
            'admin_manager_action' => $this->admin_manager_action,
            'admin_manager_action_name' => $this->getActionName($this->admin_manager_action),
            'medical_director_action' => $this->medical_director_action,
            'medical_director_action_name' => $this->getActionName($this->medical_director_action),
            'gm_action' => $this->gm_action,
            'gm_action_name' => $this->getActionName($this->gm_action),
            'admin_manager_comment' => $this->admin_manager_comment,
            'medical_director_comment' => $this->medical_director_comment,
            'gm_comment' => $this->gm_comment,
            'approved_by' => $this->approved_by,
            'approved_by_user' => $this->whenLoaded('approvedBy', fn() => [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ]),
            'gm_approved_by' => $this->gm_approved_by,
            'gm_approved_by_user' => $this->whenLoaded('gmApprovedBy', fn() => [
                'id' => $this->gmApprovedBy->id,
                'name' => $this->gmApprovedBy->name,
            ]),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'gm_approved_at' => $this->gm_approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_admin_manager', 'pending_medical_director', 'pending_general_manager' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get action name in Arabic
     */
    private function getActionName(?string $action): ?string
    {
        if (!$action) {
            return null;
        }

        return match ($action) {
            'approve' => 'اعتماد',
            'forward_to_gm' => 'تحويل للمدير العام',
            'reject' => 'رفض',
            default => $action,
        };
    }
}
