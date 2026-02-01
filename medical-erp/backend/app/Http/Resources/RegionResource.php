<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'code' => $this->code,
            'counties_count' => $this->whenCounted('counties'),
            'counties' => CountyResource::collection($this->whenLoaded('counties')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
