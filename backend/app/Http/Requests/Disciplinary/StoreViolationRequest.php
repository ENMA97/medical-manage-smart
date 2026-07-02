<?php

namespace App\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class StoreViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'violation_type_id' => 'required|exists:violation_types,id',
            'violation_date' => 'required|date',
            'violation_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'description' => 'required|string|max:2000',
            'description_ar' => 'nullable|string|max:2000',
            'witnesses' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'violation_type_id.required' => 'نوع المخالفة مطلوب',
            'violation_date.required' => 'تاريخ المخالفة مطلوب',
            'description.required' => 'وصف المخالفة مطلوب',
        ];
    }
}
