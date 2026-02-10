<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // قواعد إضافية حسب نوع الحركة
        $routeName = $this->route()->getName();

        if (str_contains($routeName, 'receive')) {
            $rules['to_warehouse_id'] = ['required', 'uuid', 'exists:warehouses,id'];
        } elseif (str_contains($routeName, 'issue')) {
            $rules['from_warehouse_id'] = ['required', 'uuid', 'exists:warehouses,id'];
        } elseif (str_contains($routeName, 'transfer')) {
            $rules['from_warehouse_id'] = ['required', 'uuid', 'exists:warehouses,id'];
            $rules['to_warehouse_id'] = ['required', 'uuid', 'exists:warehouses,id', 'different:from_warehouse_id'];
        } elseif (str_contains($routeName, 'adjust')) {
            $rules['warehouse_id'] = ['required', 'uuid', 'exists:warehouses,id'];
            $rules['adjustment_type'] = ['required', 'in:increase,decrease'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'الصنف مطلوب',
            'item_id.exists' => 'الصنف غير موجود',
            'quantity.required' => 'الكمية مطلوبة',
            'quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
            'from_warehouse_id.required' => 'المستودع المصدر مطلوب',
            'from_warehouse_id.exists' => 'المستودع المصدر غير موجود',
            'to_warehouse_id.required' => 'المستودع الوجهة مطلوب',
            'to_warehouse_id.exists' => 'المستودع الوجهة غير موجود',
            'to_warehouse_id.different' => 'المستودع الوجهة يجب أن يختلف عن المصدر',
            'warehouse_id.required' => 'المستودع مطلوب',
        ];
    }
}
