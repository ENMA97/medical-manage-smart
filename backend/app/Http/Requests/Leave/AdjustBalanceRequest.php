<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
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
            'days' => [
                'required',
                'numeric',
                'not_in:0',
                'min:-365',
                'max:365',
            ],
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'days' => 'عدد الأيام',
            'reason' => 'السبب',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'days.required' => 'يجب تحديد عدد الأيام',
            'days.numeric' => 'عدد الأيام يجب أن يكون رقم',
            'days.not_in' => 'عدد الأيام يجب أن يكون مختلف عن صفر',
            'days.min' => 'عدد الأيام للخصم يجب ألا يتجاوز 365 يوم',
            'days.max' => 'عدد الأيام للإضافة يجب ألا يتجاوز 365 يوم',
            'reason.required' => 'يجب ذكر سبب التعديل',
            'reason.min' => 'السبب يجب أن يكون 5 أحرف على الأقل',
            'reason.max' => 'السبب يجب ألا يتجاوز 500 حرف',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // التحقق من أن الخصم لا يجعل الرصيد سالب
            $leaveBalance = $this->route('leaveBalance');

            if ($leaveBalance && $this->days < 0) {
                $newBalance = $leaveBalance->remaining_days + $this->days;

                if ($newBalance < 0) {
                    $validator->errors()->add(
                        'days',
                        "لا يمكن خصم أكثر من الرصيد المتبقي ({$leaveBalance->remaining_days} يوم)"
                    );
                }
            }
        });
    }
}
