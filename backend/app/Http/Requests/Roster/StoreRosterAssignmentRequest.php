<?php

namespace App\Http\Requests\Roster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRosterAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roster_id' => ['nullable', 'uuid', 'exists:rosters,id'],
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'shift_pattern_id' => ['nullable', 'uuid', 'exists:shift_patterns,id'],
            'assignment_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['regular', 'overtime', 'on_call', 'off'])],
            'scheduled_start' => ['nullable', 'date_format:H:i'],
            'scheduled_end' => ['nullable', 'date_format:H:i'],
            'scheduled_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_overtime' => ['sometimes', 'boolean'],
            'overtime_rate' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'employee_id.exists' => 'الموظف غير موجود',
            'shift_pattern_id.exists' => 'نمط الوردية غير موجود',
            'assignment_date.required' => 'تاريخ التعيين مطلوب',
            'type.required' => 'نوع التعيين مطلوب',
            'scheduled_start.date_format' => 'صيغة وقت البداية غير صحيحة',
            'scheduled_end.date_format' => 'صيغة وقت النهاية غير صحيحة',
        ];
    }
}
