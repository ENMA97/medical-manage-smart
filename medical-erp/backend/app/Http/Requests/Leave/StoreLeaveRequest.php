<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
            'reason_ar' => 'nullable|string|max:1000',
            'substitute_employee_id' => 'nullable|exists:employees,id',
            'contact_during_leave' => 'nullable|string|max:255',
            'address_during_leave' => 'nullable|string|max:500',
            'leave_balance_id' => 'nullable|exists:leave_balances,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'leave_type_id.required' => 'نوع الإجازة مطلوب',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'end_date.required' => 'تاريخ النهاية مطلوب',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'total_days.required' => 'عدد الأيام مطلوب',
        ];
    }
}
