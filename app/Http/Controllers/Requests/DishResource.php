<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DishResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => $this->image_path ? Storage::url($this->image_path) : null,
            'is_available' => $this->is_available,
            'category' => new MenuCategoryResource($this->whenLoaded('category')),
            // данные предзаказа (есть только при загрузке блюд через бронь)
            'quantity' => $this->whenPivotLoaded('booking_dish', fn () => $this->pivot->quantity),
            'order_price' => $this->whenPivotLoaded('booking_dish', fn () => $this->pivot->price),
        ];
    }
}