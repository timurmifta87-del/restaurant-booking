<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Промежуточная модель «бронь — блюдо» (таблица booking_dish) */
class PreOrder extends Pivot
{
    protected $table = 'booking_dish';

    public $incrementing = true;

    protected $fillable = ['booking_id', 'dish_id', 'quantity', 'price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}