<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'loan_type' => ['required', Rule::in(['salary_advance', 'personal_loan', 'emergency_loan'])],
            'amount' => ['required', 'numeric', 'min:100'],
            'installments' => ['required', 'integer', 'min:1', 'max:24'],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'employee_id.exists' => 'الموظف غير موجود',
            'loan_type.required' => 'نوع السلفة مطلوب',
            'amount.required' => 'المبلغ مطلوب',
            'amount.min' => 'الحد الأدنى للسلفة 100 ريال',
            'installments.required' => 'عدد الأقساط مطلوب',
            'installments.min' => 'الحد الأدنى للأقساط قسط واحد',
            'installments.max' => 'الحد الأقصى للأقساط 24 قسط',
            'reason.required' => 'سبب السلفة مطلوب',
        ];
    }
}
