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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'second_name' => 'nullable|string|max:100',
            'third_name' => 'nullable|string|max:100',
            'first_name_ar' => 'nullable|string|max:100',
            'second_name_ar' => 'nullable|string|max:100',
            'third_name_ar' => 'nullable|string|max:100',
            'last_name_ar' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'personal_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'national_id' => 'nullable|string|max:20|unique:employees,national_id',
            'id_type' => 'nullable|string|max:50',
            'id_expiry_date' => 'nullable|date',
            'passport_number' => 'nullable|string|max:20',
            'passport_expiry_date' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'direct_manager_id' => 'nullable|exists:employees,id',
            'hire_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'employment_type' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date|before:today',
            'place_of_birth' => 'nullable|string|max:100',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'dependents_count' => 'nullable|integer|min:0',
            'nationality' => 'nullable|string|max:100',
            'nationality_ar' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:34',
            'gosi_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'blood_type' => 'nullable|string|max:5',
            'medical_conditions' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.required' => 'الرقم الوظيفي مطلوب',
            'employee_number.unique' => 'الرقم الوظيفي مسجل مسبقاً',
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً',
            'national_id.unique' => 'رقم الهوية مسجل مسبقاً',
        ];
    }
}
