<?php

namespace App\Http\Requests\Letter;

use Illuminate\Foundation\Http\FormRequest;

class StoreLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id' => 'required|uuid|exists:letter_templates,id',
            'employee_id' => 'required|uuid|exists:employees,id',
            'variables' => 'nullable|array',
            'variables.*' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'يجب اختيار قالب الخطاب',
            'template_id.exists' => 'قالب الخطاب غير موجود',
            'employee_id.required' => 'يجب تحديد الموظف',
            'employee_id.exists' => 'الموظف غير موجود',
        ];
    }
}
