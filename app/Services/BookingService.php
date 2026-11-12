<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Dish;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    private const RELATIONS = ['user', 'table.hall', 'dishes'];

    /** Клиент видит только свои брони, администратор видит все */
    public function paginate(User $actor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Booking::query()
            ->with(self::RELATIONS)
            ->when(! $actor->isAdmin(), fn ($query) => $query->where('user_id', $actor->id))
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('starts_at', $filters['date']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(
                isset($filters['hall_id']),
                fn ($query) => $query->whereHas('table', fn ($t) => $t->where('hall_id', $filters['hall_id']))
            )
            ->orderByDesc('starts_at')
            ->paginate($perPage);
    }

    public function find(User $actor, Booking $booking): Booking
    {
        $this->authorizeAccess($actor, $booking);

        return $booking->load(self::RELATIONS);
    }

    /** Ключевой сценарий: создание брони с проверкой занятости и предзаказом */
    public function create(User $actor, array $data): Booking
    {
        return DB::transaction(function () use ($actor, $data) {
            // блокируем строку столика, чтобы две брони не прошли одновременно
            $table = Table::whereKey($data['table_id'])->lockForUpdate()->firstOrFail();
            $start = Carbon::parse($data['starts_at']);
            $end = Carbon::parse($data['ends_at']);

            if (! $table->is_active) {
                abort(409, 'Столик недоступен для бронирования.');
            }

            if ($data['guests_count'] > $table->seats) {
                throw ValidationException::withMessages([
                    'guests_count' => ['Количество гостей превышает вместимость столика (' . $table->seats . ').'],
                ]);
            }

            if ($this->isBusy($table->id, $start, $end)) {
                abort(409, 'Столик уже занят на выбранное время.');
            }

            $booking = Booking::create([
                'user_id' => $actor->id,
                'table_id' => $table->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'guests_count' => $data['guests_count'],
                'status' => BookingStatus::New,
                'comment' => $data['comment'] ?? null,
            ]);

            if (! empty($data['dishes'])) {
                // sync: список блюд предзаказа задаётся целиком
                $booking->dishes()->sync($this->preOrderPayload($data['dishes']));
            }

            return $booking->load(self::RELATIONS);
        });
    }

    public function cancel(User $actor, Booking $booking): Booking
    {
        $this->authorizeAccess($actor, $booking);

        if ($booking->status === BookingStatus::Cancelled) {
            abort(409, 'Бронь уже отменена.');
        }

        if (! $actor->isAdmin() && now()->addHours(2)->greaterThan($booking->starts_at)) {
            abort(409, 'Отменить бронь можно не позднее чем за 2 часа до начала.');
        }

        $booking->update(['status' => BookingStatus::Cancelled]);

        return $booking->load(self::RELATIONS);
    }

    public function updateStatus(User $actor, Booking $booking, string $status): Booking
    {
        if (! $actor->isAdmin()) {
            abort(403, 'Менять статус брони может только администратор.');
        }

        if ($booking->status === BookingStatus::Cancelled) {
            abort(409, 'Статус отменённой брони изменить нельзя.');
        }

        $booking->update(['status' => $status]);

        return $booking->load(self::RELATIONS);
    }

    /** Добавить блюдо в предзаказ (attach) */
    public function addDish(User $actor, Booking $booking, array $data): Booking
    {
        $this->authorizeAccess($actor, $booking);
        $this->ensureEditable($booking);

        if ($booking->dishes()->whereKey($data['dish_id'])->exists()) {
            abort(409, 'Это блюдо уже есть в предзаказе.');
        }

        $booking->dishes()->attach($this->preOrderPayload([$data]));

        return $booking->load(self::RELATIONS);
    }

    /** Убрать блюдо из предзаказа (detach) */
    public function removeDish(User $actor, Booking $booking, Dish $dish): void
    {
        $this->authorizeAccess($actor, $booking);
        $this->ensureEditable($booking);

        if (! $booking->dishes()->whereKey($dish->id)->exists()) {
            abort(404, 'Этого блюда нет в предзаказе.');
        }

        $booking->dishes()->detach($dish->id);
    }

    private function isBusy(int $tableId, Carbon $start, Carbon $end): bool
    {
        return Booking::query()
            ->where('table_id', $tableId)
            ->whereIn('status', BookingStatus::active())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    /** Готовит данные для attach/sync: [dish_id => [quantity, price]] */
    private function preOrderPayload(array $items): array
    {
        $dishes = Dish::whereIn('id', collect($items)->pluck('dish_id'))->get()->keyBy('id');
        $payload = [];

        foreach ($items as $item) {
            $dish = $dishes[$item['dish_id']];

            if (! $dish->is_available) {
                throw ValidationException::withMessages([
                    'dishes' => ['Блюдо «' . $dish->name . '» сейчас недоступно.'],
                ]);
            }

            $payload[$dish->id] = [
                'quantity' => $item['quantity'],
                'price' => $dish->price, // цена фиксируется на момент заказа
            ];
        }

        return $payload;
    }

    private function authorizeAccess(User $actor, Booking $booking): void
    {
        if (! $actor->isAdmin() && $booking->user_id !== $actor->id) {
            abort(403, 'Нет доступа к этой брони.');
        }
    }

    private function ensureEditable(Booking $booking): void
    {
        if ($booking->status === BookingStatus::Cancelled) {
            abort(409, 'Бронь отменена, изменять предзаказ нельзя.');
        }
    }
}