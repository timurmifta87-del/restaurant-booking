<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'guests_count' => $this->guests_count,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'comment' => $this->comment,
            'user' => new UserResource($this->whenLoaded('user')),
            'table' => new TableResource($this->whenLoaded('table')),
            'dishes' => DishResource::collection($this->whenLoaded('dishes')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
