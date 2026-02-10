<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'contract_number' => ['required', 'string', 'max:50', 'unique:contracts,contract_number'],
            'type' => ['required', Rule::in(['full_time', 'part_time', 'tamheer', 'percentage', 'locum'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_indefinite' => ['sometimes', 'boolean'],

            // الراتب والبدلات
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transportation_allowance' => ['nullable', 'numeric', 'min:0'],
            'food_allowance' => ['nullable', 'numeric', 'min:0'],
            'phone_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowances' => ['nullable', 'numeric', 'min:0'],
            'allowance_details' => ['nullable', 'array'],

            // ساعات العمل
            'working_hours_per_week' => ['required', 'integer', 'min:1', 'max:60'],
            'working_days_per_week' => ['required', 'integer', 'min:1', 'max:7'],

            // الإجازات
            'annual_leave_days' => ['required', 'integer', 'min:21'],
            'sick_leave_days' => ['nullable', 'integer', 'min:0'],

            // للتمهير
            'tamheer_stipend' => ['nullable', 'numeric', 'min:0'],

            // للنسبة
            'percentage_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'employee_id.exists' => 'الموظف غير موجود',
            'contract_number.required' => 'رقم العقد مطلوب',
            'contract_number.unique' => 'رقم العقد مستخدم مسبقاً',
            'type.required' => 'نوع العقد مطلوب',
            'start_date.required' => 'تاريخ بداية العقد مطلوب',
            'end_date.after' => 'تاريخ نهاية العقد يجب أن يكون بعد تاريخ البداية',
            'basic_salary.required' => 'الراتب الأساسي مطلوب',
            'basic_salary.min' => 'الراتب الأساسي يجب أن يكون أكبر من صفر',
            'working_hours_per_week.required' => 'ساعات العمل الأسبوعية مطلوبة',
            'working_days_per_week.required' => 'أيام العمل الأسبوعية مطلوبة',
            'annual_leave_days.required' => 'أيام الإجازة السنوية مطلوبة',
            'annual_leave_days.min' => 'الحد الأدنى للإجازة السنوية 21 يوم',
        ];
    }
}
