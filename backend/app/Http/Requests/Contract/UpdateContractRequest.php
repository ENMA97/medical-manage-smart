<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contractId = $this->route('contract');

        return [
            'contract_number' => "sometimes|string|max:50|unique:contracts,contract_number,{$contractId}",
            'contract_type' => 'sometimes|in:full_time,part_time,temporary,tamheer,percentage,locum,probation',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'basic_salary' => 'sometimes|numeric|min:0',
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
            'status' => 'sometimes|in:draft,active,expired,terminated,renewed',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'contract_type.in' => 'نوع العقد غير صالح',
            'end_date.after' => 'تاريخ انتهاء العقد يجب أن يكون بعد تاريخ البداية',
            'basic_salary.min' => 'الراتب الأساسي لا يمكن أن يكون سالباً',
            'status.in' => 'حالة العقد غير صالحة',
        ];
    }
}
