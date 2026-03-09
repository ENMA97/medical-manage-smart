<?php

namespace App\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class FormCommitteeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'chairman_id' => 'required|exists:employees,id',
            'deadline' => 'nullable|date|after:today',
            'mandate' => 'nullable|string',
            'mandate_ar' => 'nullable|string',
            'members' => 'required|array|min:2',
            'members.*.employee_id' => 'required|exists:employees,id',
            'members.*.role' => 'required|in:chairman,member,secretary,observer',
            'members.*.role_ar' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم اللجنة مطلوب',
            'name_ar.required' => 'اسم اللجنة بالعربي مطلوب',
            'chairman_id.required' => 'رئيس اللجنة مطلوب',
            'members.required' => 'أعضاء اللجنة مطلوبون',
            'members.min' => 'يجب أن تضم اللجنة عضوين على الأقل',
        ];
    }
}
