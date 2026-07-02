<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leave_type');

        return [
            'code' => "sometimes|string|max:20|unique:leave_types,code,{$leaveTypeId}",
            'name' => 'sometimes|string|max:100',
            'name_ar' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'max_days_per_year' => 'sometimes|integer|min:0',
            'min_request_days' => 'nullable|integer|min:1',
            'max_request_days' => 'nullable|integer|min:1',
            'requires_approval' => 'boolean',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'gender_specific' => 'nullable|in:male,female',
            'carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'رمز نوع الإجازة مستخدم مسبقاً',
            'max_days_per_year.min' => 'أيام الإجازة لا يمكن أن تكون سالبة',
        ];
    }
}
