<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'seats' => $this->seats,
            'is_active' => $this->is_active,
            'hall' => new HallResource($this->whenLoaded('hall')),
        ];
    }
}