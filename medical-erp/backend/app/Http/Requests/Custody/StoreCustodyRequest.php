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
            'employee_id' => 'required|exists:employees,id',
            'item_name' => 'required|string|max:255',
            'item_name_ar' => 'nullable|string|max:255',
            'item_type' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'asset_tag' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'condition_on_delivery' => 'nullable|string',
            'delivery_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after:delivery_date',
            'delivered_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب',
            'item_name.required' => 'اسم العهدة مطلوب',
            'delivery_date.required' => 'تاريخ التسليم مطلوب',
        ];
    }
}
