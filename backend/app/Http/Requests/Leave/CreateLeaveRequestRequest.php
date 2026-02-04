<?php

namespace App\Http\Requests\Leave;

use App\Models\Leave\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLeaveRequestRequest extends FormRequest
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
            'employee_id' => [
                'required',
                'uuid',
                'exists:employees,id',
            ],
            'leave_type_id' => [
                'required',
                'uuid',
                'exists:leave_types,id',
            ],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'delegate_id' => [
                'nullable',
                'uuid',
                'exists:employees,id',
                'different:employee_id',
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'contact_during_leave' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address_during_leave' => [
                'nullable',
                'string',
                'max:500',
            ],
            'attachments' => [
                'nullable',
                'array',
            ],
            'attachments.*' => [
                'file',
                'max:5120', // 5MB
                'mimes:pdf,jpg,jpeg,png,doc,docx',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'الموظف',
            'leave_type_id' => 'نوع الإجازة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'delegate_id' => 'القائم بالعمل',
            'reason' => 'سبب الإجازة',
            'contact_during_leave' => 'رقم التواصل',
            'address_during_leave' => 'العنوان خلال الإجازة',
            'attachments' => 'المرفقات',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'يجب تحديد الموظف',
            'employee_id.exists' => 'الموظف غير موجود',
            'leave_type_id.required' => 'يجب تحديد نوع الإجازة',
            'leave_type_id.exists' => 'نوع الإجازة غير موجود',
            'start_date.required' => 'يجب تحديد تاريخ البداية',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
            'end_date.required' => 'يجب تحديد تاريخ النهاية',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو مساوي لتاريخ البداية',
            'delegate_id.different' => 'القائم بالعمل يجب أن يكون شخص مختلف',
            'reason.required' => 'يجب ذكر سبب الإجازة',
            'reason.min' => 'يجب أن يكون السبب 10 أحرف على الأقل',
            'attachments.*.max' => 'حجم الملف يجب ألا يتجاوز 5 ميجابايت',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // التحقق من أن نوع الإجازة يتطلب مرفقات
            if ($this->leave_type_id) {
                $leaveType = LeaveType::find($this->leave_type_id);
                if ($leaveType && $leaveType->requires_attachment && empty($this->attachments)) {
                    $validator->errors()->add('attachments', 'هذا النوع من الإجازات يتطلب إرفاق مستندات');
                }
            }
        });
    }
}
