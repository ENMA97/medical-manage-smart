<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department');

        return [
            'name_ar' => 'sometimes|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'code' => "nullable|string|max:20|unique:departments,code,{$departmentId}",
            'manager_id' => 'nullable|uuid|exists:employees,id',
            'parent_id' => "nullable|uuid|exists:departments,id|not_in:{$departmentId}",
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }
}
