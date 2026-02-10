<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            // البيانات الأساسية
            'employee_number' => ['sometimes', 'string', 'max:50', Rule::unique('employees')->ignore($employeeId)],
            'first_name_ar' => ['sometimes', 'string', 'max:100'],
            'last_name_ar' => ['sometimes', 'string', 'max:100'],
            'first_name_en' => ['nullable', 'string', 'max:100'],
            'last_name_en' => ['nullable', 'string', 'max:100'],

            // معلومات الهوية
            'national_id' => ['sometimes', 'string', 'max:20', Rule::unique('employees')->ignore($employeeId)],
            'nationality' => ['sometimes', 'string', 'max:50'],
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],

            // معلومات التواصل
            'email' => ['sometimes', 'email', Rule::unique('employees')->ignore($employeeId)],
            'phone' => ['sometimes', 'string', 'max:20'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],

            // معلومات التوظيف
            'department_id' => ['sometimes', 'uuid', 'exists:departments,id'],
            'position_id' => ['sometimes', 'uuid', 'exists:positions,id'],
            'hire_date' => ['sometimes', 'date'],
            'employee_type' => ['sometimes', Rule::in(['doctor', 'medical_staff', 'administrative', 'support', 'management'])],

            // المعلومات البنكية
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],

            // معلومات GOSI
            'gosi_number' => ['nullable', 'string', 'max:20'],

            // الحالة
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.unique' => 'رقم الموظف مستخدم مسبقاً',
            'national_id.unique' => 'رقم الهوية مسجل مسبقاً',
            'date_of_birth.before' => 'تاريخ الميلاد يجب أن يكون قبل اليوم',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'department_id.exists' => 'القسم المحدد غير موجود',
            'position_id.exists' => 'المنصب المحدد غير موجود',
        ];
    }
}
