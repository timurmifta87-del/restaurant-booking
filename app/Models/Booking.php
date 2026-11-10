<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'table_id',
        'starts_at',
        'ends_at',
        'guests_count',
        'status',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'guests_count' => 'integer',
            'status' => BookingStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /** Блюда предзаказа (многие ко многим через booking_dish) */
    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class, 'booking_dish')
            ->using(PreOrder::class)
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function preOrders(): HasMany
    {
        return $this->hasMany(PreOrder::class);
    }
}