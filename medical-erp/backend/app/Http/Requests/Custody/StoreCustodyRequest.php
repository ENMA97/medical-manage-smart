<?php

namespace App\Http\Requests\Custody;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustodyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|uuid|exists:employees,id',
            'item_name' => 'required|string|max:255',
            'item_type' => 'required|in:laptop,phone,car,key,badge,other',
            'serial_number' => 'nullable|string|max:100',
            'assigned_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'item_name.required' => 'اسم العهدة مطلوب',
            'item_type.required' => 'نوع العهدة مطلوب',
            'assigned_date.required' => 'تاريخ التسليم مطلوب',
        ];
    }
}
