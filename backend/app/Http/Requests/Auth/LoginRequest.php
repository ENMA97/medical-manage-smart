<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.required' => 'الرقم الوظيفي مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
        ];
    }
}
