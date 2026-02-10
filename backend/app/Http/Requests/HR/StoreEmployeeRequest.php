<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // البيانات الأساسية
            'employee_number' => ['required', 'string', 'max:50', 'unique:employees,employee_number'],
            'first_name_ar' => ['required', 'string', 'max:100'],
            'last_name_ar' => ['required', 'string', 'max:100'],
            'first_name_en' => ['nullable', 'string', 'max:100'],
            'last_name_en' => ['nullable', 'string', 'max:100'],

            // معلومات الهوية
            'national_id' => ['required', 'string', 'max:20', 'unique:employees,national_id'],
            'nationality' => ['required', 'string', 'max:50'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],

            // معلومات التواصل
            'email' => ['required', 'email', 'unique:employees,email'],
            'phone' => ['required', 'string', 'max:20'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],

            // معلومات التوظيف
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'position_id' => ['required', 'uuid', 'exists:positions,id'],
            'hire_date' => ['required', 'date'],
            'employee_type' => ['required', Rule::in(['doctor', 'medical_staff', 'administrative', 'support', 'management'])],

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
            'employee_number.required' => 'رقم الموظف مطلوب',
            'employee_number.unique' => 'رقم الموظف مستخدم مسبقاً',
            'first_name_ar.required' => 'الاسم الأول بالعربية مطلوب',
            'last_name_ar.required' => 'اسم العائلة بالعربية مطلوب',
            'national_id.required' => 'رقم الهوية مطلوب',
            'national_id.unique' => 'رقم الهوية مسجل مسبقاً',
            'nationality.required' => 'الجنسية مطلوبة',
            'gender.required' => 'الجنس مطلوب',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب',
            'date_of_birth.before' => 'تاريخ الميلاد يجب أن يكون قبل اليوم',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'phone.required' => 'رقم الهاتف مطلوب',
            'department_id.required' => 'القسم مطلوب',
            'department_id.exists' => 'القسم المحدد غير موجود',
            'position_id.required' => 'المنصب مطلوب',
            'position_id.exists' => 'المنصب المحدد غير موجود',
            'hire_date.required' => 'تاريخ التعيين مطلوب',
            'employee_type.required' => 'نوع الموظف مطلوب',
        ];
    }
}
