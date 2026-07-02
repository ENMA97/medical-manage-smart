<?php

namespace App\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class IssueDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'penalty_type' => 'required|string|max:100',
            'penalty_type_ar' => 'required|string|max:100',
            'penalty_details' => 'nullable|string',
            'penalty_details_ar' => 'nullable|string',
            'deduction_amount' => 'nullable|numeric|min:0',
            'deduction_days' => 'nullable|integer|min:0',
            'suspension_days' => 'nullable|integer|min:0',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'justification' => 'required|string',
            'justification_ar' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'penalty_type.required' => 'نوع العقوبة مطلوب',
            'penalty_type_ar.required' => 'نوع العقوبة بالعربي مطلوب',
            'effective_date.required' => 'تاريخ سريان القرار مطلوب',
            'justification.required' => 'مبررات القرار مطلوبة',
        ];
    }
}
