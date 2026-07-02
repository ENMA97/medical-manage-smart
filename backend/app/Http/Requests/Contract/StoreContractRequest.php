<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'contract_number' => 'nullable|string|max:50|unique:contracts,contract_number',
            'contract_type' => 'required|in:full_time,part_time,temporary,tamheer,percentage,locum,probation',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'basic_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
            'phone_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'duration_months' => 'nullable|integer|min:1',
            'probation_days' => 'nullable|integer|min:0',
            'annual_leave_days' => 'nullable|integer|min:0',
            'sick_leave_days' => 'nullable|integer|min:0',
            'notice_period_days' => 'nullable|integer|min:0',
            'terms_and_conditions' => 'nullable|string',
            'special_clauses' => 'nullable|string',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'previous_contract_id' => 'nullable|exists:contracts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'contract_type.required' => 'نوع العقد مطلوب',
            'start_date.required' => 'تاريخ بداية العقد مطلوب',
            'basic_salary.required' => 'الراتب الأساسي مطلوب',
        ];
    }
}
