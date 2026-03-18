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
            'code' => 'required|string|max:20|unique:departments,code',
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز القسم مطلوب',
            'code.unique' => 'رمز القسم مسجل مسبقاً',
            'name.required' => 'اسم القسم مطلوب',
            'name_ar.required' => 'اسم القسم بالعربية مطلوب',
        ];
    }
}
