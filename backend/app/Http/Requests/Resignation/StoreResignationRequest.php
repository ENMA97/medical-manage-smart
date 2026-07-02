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
            'employee_id' => 'required|exists:employees,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'type' => 'nullable|string|max:50',
            'request_date' => 'required|date',
            'last_working_day' => 'required|date|after_or_equal:request_date',
            'effective_date' => 'nullable|date|after_or_equal:last_working_day',
            'notice_period_days' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:1000',
            'reason_ar' => 'nullable|string|max:1000',
            'direct_manager_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'reason.required' => 'سبب الاستقالة مطلوب',
            'request_date.required' => 'تاريخ الطلب مطلوب',
            'last_working_day.required' => 'تاريخ آخر يوم عمل مطلوب',
            'last_working_day.after_or_equal' => 'تاريخ آخر يوم عمل يجب أن يكون بعد تاريخ الطلب',
        ];
    }
}
