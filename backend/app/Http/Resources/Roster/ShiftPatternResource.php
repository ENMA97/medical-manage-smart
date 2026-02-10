<?php

namespace App\Http\Resources\Roster;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftPatternResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'name' => app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar),
            'description' => $this->description,
            'type' => $this->type,
            'type_name' => $this->type_name ?? null,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'break_start' => $this->break_start,
            'break_end' => $this->break_end,
            'break_duration_minutes' => $this->break_duration_minutes,
            'scheduled_hours' => $this->scheduled_hours,
            'duration_hours' => $this->duration_hours ?? null,
            'color_code' => $this->color_code,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
