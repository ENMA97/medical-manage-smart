<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:20|unique:departments,code',
            'manager_id' => 'nullable|uuid|exists:employees,id',
            'parent_id' => 'nullable|uuid|exists:departments,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم القسم بالعربية مطلوب',
            'code.unique' => 'رمز القسم مسجل مسبقاً',
        ];
    }
}
