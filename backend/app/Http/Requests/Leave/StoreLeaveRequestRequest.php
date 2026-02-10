<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'uuid', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['sometimes', 'boolean'],
            'half_day_period' => ['required_if:is_half_day,true', Rule::in(['morning', 'afternoon'])],
            'reason' => ['nullable', 'string', 'max:1000'],
            'reason_ar' => ['nullable', 'string', 'max:1000'],
            'contact_during_leave' => ['nullable', 'string', 'max:50'],
            'address_during_leave' => ['nullable', 'string', 'max:500'],
            'delegate_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'job_tasks' => ['nullable', 'string', 'max:2000'],
            'job_tasks_ar' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'نوع الإجازة مطلوب',
            'leave_type_id.exists' => 'نوع الإجازة غير موجود',
            'start_date.required' => 'تاريخ بداية الإجازة مطلوب',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
            'end_date.required' => 'تاريخ نهاية الإجازة مطلوب',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
            'half_day_period.required_if' => 'يرجى تحديد فترة نصف اليوم',
            'delegate_employee_id.exists' => 'الموظف البديل غير موجود',
            'attachments.*.max' => 'حجم المرفق يجب ألا يتجاوز 5 ميجابايت',
            'attachments.*.mimes' => 'المرفقات يجب أن تكون PDF أو صورة',
        ];
    }
}
