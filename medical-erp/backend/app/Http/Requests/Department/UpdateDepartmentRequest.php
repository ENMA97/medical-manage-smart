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
            'code' => "sometimes|string|max:20|unique:departments,code,{$departmentId}",
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'parent_id' => "nullable|exists:departments,id|not_in:{$departmentId}",
            'manager_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];
    }
}
