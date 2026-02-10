<?php

namespace App\Http\Resources\Payroll;

use App\Http\Resources\HR\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_number' => $this->payroll_number,
            'employee' => $this->whenLoaded('employee', fn() => new EmployeeResource($this->employee)),
            'period_year' => $this->period_year,
            'period_month' => $this->period_month,
            'period' => $this->period_year . '-' . str_pad($this->period_month, 2, '0', STR_PAD_LEFT),
            'status' => $this->status,

            // الراتب
            'basic_salary' => $this->basic_salary,
            'housing_allowance' => $this->housing_allowance,
            'transportation_allowance' => $this->transportation_allowance,
            'food_allowance' => $this->food_allowance,
            'phone_allowance' => $this->phone_allowance,
            'other_allowances' => $this->other_allowances,
            'total_allowances' => $this->total_allowances,
            'gross_salary' => $this->gross_salary,

            // الخصومات
            'gosi_employee' => $this->gosi_employee,
            'gosi_employer' => $this->gosi_employer,
            'absence_deduction' => $this->absence_deduction,
            'loan_deduction' => $this->loan_deduction,
            'other_deductions' => $this->other_deductions,
            'total_deductions' => $this->total_deductions,

            // الإضافات
            'overtime_amount' => $this->overtime_amount,
            'bonus' => $this->bonus,
            'commission' => $this->commission,
            'other_earnings' => $this->other_earnings,
            'total_earnings' => $this->total_earnings,

            // الصافي
            'net_salary' => $this->net_salary,

            // أيام العمل
            'working_days' => $this->working_days,
            'actual_working_days' => $this->actual_working_days,
            'absence_days' => $this->absence_days,
            'overtime_hours' => $this->overtime_hours,

            // البنود التفصيلية
            'items' => PayrollItemResource::collection($this->whenLoaded('items')),

            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
