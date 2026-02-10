<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,

            // الاسم
            'first_name_ar' => $this->first_name_ar,
            'last_name_ar' => $this->last_name_ar,
            'first_name_en' => $this->first_name_en,
            'last_name_en' => $this->last_name_en,
            'full_name_ar' => $this->first_name_ar . ' ' . $this->last_name_ar,
            'full_name_en' => $this->first_name_en ? $this->first_name_en . ' ' . $this->last_name_en : null,

            // معلومات الهوية
            'national_id' => $this->national_id,
            'nationality' => $this->nationality,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->date_of_birth?->age,
            'marital_status' => $this->marital_status,

            // معلومات التواصل
            'email' => $this->email,
            'phone' => $this->phone,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'address' => $this->address,

            // معلومات التوظيف
            'department' => $this->whenLoaded('department', fn() => new DepartmentResource($this->department)),
            'position' => $this->whenLoaded('position', fn() => new PositionResource($this->position)),
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'years_of_service' => $this->hire_date?->diffInYears(now()),
            'employee_type' => $this->employee_type,

            // المعلومات البنكية
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'iban' => $this->iban,

            // معلومات GOSI
            'gosi_number' => $this->gosi_number,

            // الحالة
            'is_active' => $this->is_active,
            'status' => $this->status,

            // العقد الحالي
            'current_contract' => $this->whenLoaded('activeContract', fn() => new ContractResource($this->activeContract)),

            // العهد النشطة
            'active_custodies_count' => $this->whenCounted('activeCustodies'),

            // التواريخ
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
