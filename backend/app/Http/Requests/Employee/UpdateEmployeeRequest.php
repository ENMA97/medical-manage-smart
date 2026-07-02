<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

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
            'employee_number' => "sometimes|string|max:50|unique:employees,employee_number,{$employeeId}",
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'second_name' => 'nullable|string|max:100',
            'third_name' => 'nullable|string|max:100',
            'first_name_ar' => 'nullable|string|max:100',
            'second_name_ar' => 'nullable|string|max:100',
            'third_name_ar' => 'nullable|string|max:100',
            'last_name_ar' => 'nullable|string|max:100',
            'email' => "sometimes|nullable|email|max:255|unique:employees,email,{$employeeId}",
            'personal_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'national_id' => "nullable|string|max:20|unique:employees,national_id,{$employeeId}",
            'id_type' => 'nullable|string|max:50',
            'id_expiry_date' => 'nullable|date',
            'passport_number' => 'nullable|string|max:20',
            'passport_expiry_date' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'direct_manager_id' => 'nullable|exists:employees,id',
            'hire_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'employment_type' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive,suspended,terminated',
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
}
