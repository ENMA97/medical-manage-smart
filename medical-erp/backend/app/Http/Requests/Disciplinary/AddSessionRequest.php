<?php

namespace App\Http\Requests\Disciplinary;

use Illuminate\Foundation\Http\FormRequest;

class AddSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'agenda' => 'nullable|string',
            'agenda_ar' => 'nullable|string',
            'minutes' => 'nullable|string',
            'minutes_ar' => 'nullable|string',
            'employee_response' => 'nullable|string',
            'employee_response_ar' => 'nullable|string',
            'employee_attended' => 'required|boolean',
            'employee_absence_reason' => 'nullable|string',
            'status' => 'required|in:scheduled,completed,postponed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'session_date.required' => 'تاريخ الجلسة مطلوب',
            'employee_attended.required' => 'حضور الموظف مطلوب',
            'status.required' => 'حالة الجلسة مطلوبة',
        ];
    }
}
