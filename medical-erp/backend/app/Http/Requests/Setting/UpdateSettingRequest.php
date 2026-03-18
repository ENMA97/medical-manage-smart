<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'required|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'يجب تحديد القيمة',
            'value.max' => 'القيمة تتجاوز الحد المسموح',
        ];
    }
}
