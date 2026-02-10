<?php

namespace App\Http\Resources\Roster;

use App\Http\Resources\HR\DepartmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RosterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roster_number' => $this->roster_number,
            'department' => $this->whenLoaded('department', fn() => new DepartmentResource($this->department)),
            'year' => $this->year,
            'month' => $this->month,
            'period' => $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT),
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'locked_at' => $this->locked_at?->toISOString(),
            'notes' => $this->notes,
            'assignments' => RosterAssignmentResource::collection($this->whenLoaded('assignments')),
            'assignments_count' => $this->whenCounted('assignments'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
