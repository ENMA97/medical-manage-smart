<?php

namespace App\Http\Resources\Leave;

use App\Http\Resources\HR\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,

            // الموظف
            'employee' => $this->whenLoaded('employee', fn() => new EmployeeResource($this->employee)),

            // نوع الإجازة
            'leave_type' => $this->whenLoaded('leaveType', fn() => new LeaveTypeResource($this->leaveType)),

            // التواريخ
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'total_days' => $this->total_days,
            'is_half_day' => $this->is_half_day,
            'half_day_period' => $this->half_day_period,

            // السبب
            'reason' => $this->reason,
            'reason_ar' => $this->reason_ar,

            // معلومات التواصل
            'contact_during_leave' => $this->contact_during_leave,
            'address_during_leave' => $this->address_during_leave,

            // القائم بالعمل
            'delegate' => $this->whenLoaded('delegate', fn() => new EmployeeResource($this->delegate)),
            'job_tasks' => $this->job_tasks,
            'job_tasks_ar' => $this->job_tasks_ar,
            'delegate_confirmed' => $this->delegate_confirmed,
            'delegate_confirmed_at' => $this->delegate_confirmed_at?->toISOString(),

            // الحالة
            'status' => $this->status,
            'status_name' => $this->status_name ?? null,

            // العودة الفعلية
            'actual_return_date' => $this->actual_return_date?->format('Y-m-d'),
            'actual_days_taken' => $this->actual_days_taken,

            // الإلغاء
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at?->toISOString(),

            // المرفقات
            'attachments' => $this->attachments,

            // الموافقات
            'approvals' => LeaveApprovalResource::collection($this->whenLoaded('approvals')),

            // قرار الإجازة
            'decision' => $this->whenLoaded('decision', fn() => new LeaveDecisionResource($this->decision)),

            // التواريخ
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
