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
            'employee_id' => 'required|uuid|exists:employees,id',
            'contract_number' => 'nullable|string|max:50|unique:contracts,contract_number',
            'type' => 'required|in:permanent,temporary,part_time,probation',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'basic_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'type.required' => 'نوع العقد مطلوب',
            'start_date.required' => 'تاريخ بداية العقد مطلوب',
            'basic_salary.required' => 'الراتب الأساسي مطلوب',
        ];
    }
}
