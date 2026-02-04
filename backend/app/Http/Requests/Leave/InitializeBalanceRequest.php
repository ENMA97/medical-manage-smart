<?php

namespace App\Http\Requests\Leave;

use App\Models\Leave\LeaveBalance;
use Illuminate\Foundation\Http\FormRequest;

class InitializeBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'uuid',
                'exists:employees,id',
            ],
            'leave_type_id' => [
                'required',
                'uuid',
                'exists:leave_types,id',
            ],
            'year' => [
                'nullable',
                'integer',
                'min:2020',
                'max:2100',
            ],
            'entitled_days' => [
                'required',
                'numeric',
                'min:0',
                'max:365',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'الموظف',
            'leave_type_id' => 'نوع الإجازة',
            'year' => 'السنة',
            'entitled_days' => 'أيام الاستحقاق',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'يجب تحديد الموظف',
            'employee_id.exists' => 'الموظف غير موجود',
            'leave_type_id.required' => 'يجب تحديد نوع الإجازة',
            'leave_type_id.exists' => 'نوع الإجازة غير موجود',
            'year.min' => 'السنة يجب أن تكون 2020 أو أحدث',
            'year.max' => 'السنة يجب ألا تتجاوز 2100',
            'entitled_days.required' => 'يجب تحديد أيام الاستحقاق',
            'entitled_days.min' => 'أيام الاستحقاق يجب أن تكون صفر أو أكثر',
            'entitled_days.max' => 'أيام الاستحقاق يجب ألا تتجاوز 365 يوم',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // التحقق من عدم وجود رصيد مسبق
            $year = $this->year ?? date('Y');

            $existing = LeaveBalance::where('employee_id', $this->employee_id)
                ->where('leave_type_id', $this->leave_type_id)
                ->where('year', $year)
                ->exists();

            if ($existing) {
                $validator->errors()->add(
                    'employee_id',
                    'يوجد رصيد مسبق لهذا الموظف ونوع الإجازة في هذه السنة'
                );
            }
        });
    }
}
