<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_name' => $this->type_name ?? null,
            'code' => $this->code,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'amount' => $this->amount,
            'is_taxable' => $this->is_taxable,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
