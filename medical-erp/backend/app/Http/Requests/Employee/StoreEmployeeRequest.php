<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_number' => 'required|string|max:50|unique:employees,employee_number',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:20|unique:employees,national_id',
            'department_id' => 'required|uuid|exists:departments,id',
            'position_id' => 'nullable|uuid|exists:positions,id',
            'date_of_birth' => 'nullable|date|before:today',
            'hire_date' => 'required|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'salary' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:34',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.required' => 'الرقم الوظيفي مطلوب',
            'employee_number.unique' => 'الرقم الوظيفي مسجل مسبقاً',
            'full_name.required' => 'اسم الموظف مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً',
            'department_id.required' => 'القسم مطلوب',
            'department_id.exists' => 'القسم غير موجود',
            'hire_date.required' => 'تاريخ التعيين مطلوب',
        ];
    }
}
