<?php

namespace App\Http\Requests\Resignation;

use Illuminate\Foundation\Http\FormRequest;

class StoreResignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
            'requested_last_day' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب الاستقالة مطلوب',
            'requested_last_day.required' => 'تاريخ آخر يوم عمل مطلوب',
            'requested_last_day.after' => 'تاريخ آخر يوم عمل يجب أن يكون في المستقبل',
        ];
    }
}
