<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code'],
            'barcode' => ['nullable', 'string', 'max:50', 'unique:inventory_items,barcode'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'uuid', 'exists:item_categories,id'],
            'type' => ['required', Rule::in(['medicine', 'consumable', 'equipment', 'surgical', 'laboratory', 'radiology', 'other'])],

            // الوحدات
            'unit' => ['required', 'string', 'max:50'],
            'secondary_unit' => ['nullable', 'string', 'max:50'],
            'conversion_rate' => ['nullable', 'numeric', 'min:0.01'],

            // للأدوية
            'generic_name' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:100'],
            'dosage_form' => ['nullable', 'string', 'max:100'],

            // المخزون
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],

            // الأسعار
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],

            // التتبع
            'track_batch' => ['sometimes', 'boolean'],
            'track_expiry' => ['sometimes', 'boolean'],
            'is_controlled' => ['sometimes', 'boolean'],
            'requires_prescription' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود الصنف مطلوب',
            'code.unique' => 'كود الصنف مستخدم مسبقاً',
            'barcode.unique' => 'الباركود مستخدم مسبقاً',
            'name_ar.required' => 'اسم الصنف بالعربية مطلوب',
            'category_id.exists' => 'الفئة غير موجودة',
            'type.required' => 'نوع الصنف مطلوب',
            'unit.required' => 'وحدة القياس مطلوبة',
        ];
    }
}
