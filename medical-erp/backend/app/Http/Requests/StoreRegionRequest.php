<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:regions,name',
            'name_ar' => 'required|string|max:255|unique:regions,name_ar',
            'code' => 'required|string|max:10|unique:regions,code',
        ];
    }
}
