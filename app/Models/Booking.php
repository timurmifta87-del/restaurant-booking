<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
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

    /** Клиент видит только свои брони, администратор — все */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('user_id', $user->id);
    }

    /** Фильтрация по статусу, залу и диапазону дат */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['hall_id'] ?? null, fn ($q, $hallId) => $q->whereHas(
                'table',
                fn ($t) => $t->where('hall_id', $hallId)
            ))
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('starts_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('starts_at', '<=', $to));
    }

    /** Поиск по части текста: комментарий, имя или email гостя */
    public function scopeSearch(Builder $query, ?string $text): Builder
    {
        if (! $text) {
            return $query;
        }

        $like = '%' . $text . '%';

        return $query->where(fn ($q) => $q
            ->where('comment', 'like', $like)
            ->orWhereHas('user', fn ($u) => $u
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)));
    }
}
