<?php

namespace App\Http\Resources\Roster;

use App\Http\Resources\HR\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RosterAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roster' => $this->whenLoaded('roster', fn() => new RosterResource($this->roster)),
            'employee' => $this->whenLoaded('employee', fn() => new EmployeeResource($this->employee)),
            'shift_pattern' => $this->whenLoaded('shiftPattern', fn() => new ShiftPatternResource($this->shiftPattern)),
            'assignment_date' => $this->assignment_date?->format('Y-m-d'),
            'type' => $this->type,
            'type_name' => $this->type_name ?? null,

            // الأوقات المجدولة
            'scheduled_start' => $this->scheduled_start,
            'scheduled_end' => $this->scheduled_end,
            'scheduled_hours' => $this->scheduled_hours,

            // الحضور الفعلي
            'actual_start' => $this->actual_start?->toISOString(),
            'actual_end' => $this->actual_end?->toISOString(),
            'actual_hours' => $this->actual_hours,
            'late_minutes' => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,

            // الوقت الإضافي
            'is_overtime' => $this->is_overtime,
            'overtime_hours' => $this->overtime_hours,
            'overtime_rate' => $this->overtime_rate,

            // الحالة
            'status' => $this->status,
            'status_name' => $this->status_name ?? null,
            'is_late' => $this->is_late ?? false,

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
