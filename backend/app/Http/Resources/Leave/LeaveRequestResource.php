<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'name' => $this->employee->name ?? $this->employee->full_name,
                'employee_number' => $this->employee->employee_number ?? null,
                'department' => $this->employee->department?->name ?? null,
                'position' => $this->employee->position ?? null,
            ]),
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'delegate_id' => $this->delegate_id,
            'delegate' => $this->whenLoaded('delegate', fn() => [
                'id' => $this->delegate->id,
                'name' => $this->delegate->name ?? $this->delegate->full_name,
            ]),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'total_days' => (float) $this->total_days,
            'working_days' => (float) $this->working_days,
            'reason' => $this->reason,
            'contact_during_leave' => $this->contact_during_leave,
            'address_during_leave' => $this->address_during_leave,
            'job_tasks' => $this->job_tasks,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'status_color' => $this->getStatusColor(),
            'current_approval_level' => $this->current_approval_level,
            'current_approval_level_name' => $this->getCurrentApprovalLevelName(),
            'rejection_reason' => $this->rejection_reason,
            'cancelled_reason' => $this->cancelled_reason,
            'attachments' => $this->attachments,
            'approvals' => LeaveApprovalResource::collection($this->whenLoaded('approvals')),
            'decision' => new LeaveDecisionResource($this->whenLoaded('decision')),
            'can_edit' => $this->canBeEdited(),
            'can_cancel' => $this->canBeCancelled(),
            'can_submit' => $this->status === 'draft',
            'submitted_at' => $this->submitted_at?->toIso8601String(),
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
            'pending_supervisor', 'pending_admin_manager', 'pending_hr', 'pending_delegate' => 'yellow',
            'form_completed' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get current approval level name
     */
    private function getCurrentApprovalLevelName(): ?string
    {
        return match ($this->current_approval_level) {
            'supervisor' => 'المشرف المباشر',
            'admin_manager' => 'المدير الإداري',
            'hr' => 'الموارد البشرية',
            'delegate' => 'القائم بالعمل',
            default => null,
        };
    }
}
