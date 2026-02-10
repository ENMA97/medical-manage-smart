<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_number' => $this->contract_number,
            'employee' => $this->whenLoaded('employee', fn() => new EmployeeResource($this->employee)),
            'type' => $this->type,
            'type_name' => $this->type_name ?? null,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_indefinite' => $this->is_indefinite,
            'is_active' => $this->is_active,

            // الراتب والبدلات
            'basic_salary' => $this->basic_salary,
            'housing_allowance' => $this->housing_allowance,
            'transportation_allowance' => $this->transportation_allowance,
            'food_allowance' => $this->food_allowance,
            'phone_allowance' => $this->phone_allowance,
            'other_allowances' => $this->other_allowances,
            'total_salary' => $this->total_salary ?? ($this->basic_salary + ($this->housing_allowance ?? 0) + ($this->transportation_allowance ?? 0) + ($this->food_allowance ?? 0) + ($this->phone_allowance ?? 0) + ($this->other_allowances ?? 0)),
            'allowance_details' => $this->allowance_details,

            // ساعات العمل
            'working_hours_per_week' => $this->working_hours_per_week,
            'working_days_per_week' => $this->working_days_per_week,

            // الإجازات
            'annual_leave_days' => $this->annual_leave_days,
            'sick_leave_days' => $this->sick_leave_days,

            // للتمهير
            'tamheer_stipend' => $this->tamheer_stipend,

            // للنسبة
            'percentage_rate' => $this->percentage_rate,

            // معلومات الإنهاء
            'termination_date' => $this->termination_date?->format('Y-m-d'),
            'termination_reason' => $this->termination_reason,

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
