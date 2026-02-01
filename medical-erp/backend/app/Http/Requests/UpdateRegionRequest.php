<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regionId = $this->route('region')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('regions', 'name')->ignore($regionId)],
            'name_ar' => ['sometimes', 'string', 'max:255', Rule::unique('regions', 'name_ar')->ignore($regionId)],
            'code' => ['sometimes', 'string', 'max:10', Rule::unique('regions', 'code')->ignore($regionId)],
        ];
    }
}
