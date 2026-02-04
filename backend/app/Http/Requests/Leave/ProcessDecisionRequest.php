<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessDecisionRequest extends FormRequest
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
            'action' => [
                'required',
                'string',
                Rule::in(['approve', 'forward_to_gm', 'reject']),
            ],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'action' => 'الإجراء',
            'comment' => 'التعليق',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'يجب تحديد الإجراء',
            'action.in' => 'الإجراء يجب أن يكون: اعتماد، تحويل للمدير العام، أو رفض',
            'comment.max' => 'التعليق يجب ألا يتجاوز 1000 حرف',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // في حالة الرفض أو التحويل يجب ذكر السبب
            if (in_array($this->action, ['reject', 'forward_to_gm']) && empty($this->comment)) {
                $message = $this->action === 'reject'
                    ? 'يجب ذكر سبب الرفض'
                    : 'يجب ذكر سبب التحويل للمدير العام';
                $validator->errors()->add('comment', $message);
            }
        });
    }
}
