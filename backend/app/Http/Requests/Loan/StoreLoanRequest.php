<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|uuid|exists:employees,id',
            'loan_amount' => 'required|numeric|min:100|max:500000',
            'monthly_deduction' => 'required|numeric|min:50',
            'start_date' => 'required|date|after_or_equal:today',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'يجب تحديد الموظف',
            'employee_id.exists' => 'الموظف غير موجود',
            'loan_amount.required' => 'يجب تحديد مبلغ السلفة',
            'loan_amount.min' => 'الحد الأدنى للسلفة 100 ريال',
            'loan_amount.max' => 'الحد الأقصى للسلفة 500,000 ريال',
            'monthly_deduction.required' => 'يجب تحديد مبلغ القسط الشهري',
            'monthly_deduction.min' => 'الحد الأدنى للقسط 50 ريال',
            'start_date.required' => 'يجب تحديد تاريخ البداية',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('loan_amount') && $this->filled('monthly_deduction')) {
                if ($this->input('monthly_deduction') > $this->input('loan_amount')) {
                    $validator->errors()->add('monthly_deduction', 'القسط الشهري لا يمكن أن يتجاوز مبلغ السلفة');
                }
            }
        });
    }
}
