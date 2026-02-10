<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'forward_to_gm'])],
            'comment' => ['nullable', 'string', 'max:1000'],
            'comment_ar' => ['nullable', 'string', 'max:1000'],
            'job_tasks_assigned' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'الإجراء مطلوب',
            'action.in' => 'الإجراء غير صالح',
        ];
    }
}
