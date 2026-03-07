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
            'full_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'national_id' => "nullable|string|max:20|unique:employees,national_id,{$employeeId}",
            'department_id' => 'sometimes|uuid|exists:departments,id',
            'position_id' => 'nullable|uuid|exists:positions,id',
            'date_of_birth' => 'nullable|date|before:today',
            'hire_date' => 'sometimes|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'salary' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:34',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,suspended,terminated',
        ];
    }
}
