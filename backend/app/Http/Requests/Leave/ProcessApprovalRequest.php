<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class ProcessApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'approved' => [
                'required',
                'boolean',
            ],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'job_tasks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'approved' => 'قرار الموافقة',
            'comment' => 'التعليق',
            'job_tasks' => 'المهام الوظيفية',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'approved.required' => 'يجب تحديد قرار الموافقة أو الرفض',
            'approved.boolean' => 'قرار الموافقة يجب أن يكون نعم أو لا',
            'comment.max' => 'التعليق يجب ألا يتجاوز 1000 حرف',
            'job_tasks.max' => 'المهام الوظيفية يجب ألا تتجاوز 2000 حرف',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // في حالة الرفض يجب ذكر السبب
            if ($this->approved === false && empty($this->comment)) {
                $validator->errors()->add('comment', 'يجب ذكر سبب الرفض');
            }
        });
    }
}
