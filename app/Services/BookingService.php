<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\BusinessException;
use App\Models\Booking;
use App\Models\Dish;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    private const RELATIONS = ['user', 'table.hall', 'dishes'];

    /** Не больше стольких активных будущих броней у одного клиента */
    public const MAX_ACTIVE_BOOKINGS = 3;

    /** Максимальная длительность брони, часов */
    public const MAX_HOURS = 4;

    /** Отменить бронь клиент может не позднее чем за столько часов */
    public const CANCEL_HOURS = 2;

    public function paginate(User $actor, array $filters = []): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'starts_at';
        $order = $filters['order'] ?? 'desc';

        return Booking::query()
            ->with(self::RELATIONS)
            ->visibleTo($actor)
            ->filter($filters)
            ->search($filters['search'] ?? null)
            ->orderBy($sort, $order)
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function load(Booking $booking): Booking
    {
        return $booking->load(self::RELATIONS);
    }

    /** Ключевой сценарий: бронирование столика с предзаказом */
    public function create(User $actor, array $data): Booking
    {
        $booking = DB::transaction(function () use ($actor, $data) {
            // блокируем строку столика: две одновременные брони не пройдут
            $table = Table::whereKey($data['table_id'])->lockForUpdate()->firstOrFail();
            $start = Carbon::parse($data['starts_at']);
            $end = Carbon::parse($data['ends_at']);

            if (! $table->is_active) {
                throw new BusinessException('Столик недоступен для бронирования.');
            }

            if ($data['guests_count'] > $table->seats) {
                throw new BusinessException(
                    'Количество гостей превышает вместимость столика (' . $table->seats . ').'
                );
            }

            if ($start->diffInMinutes($end) > self::MAX_HOURS * 60) {
                throw new BusinessException('Бронь не может быть длиннее ' . self::MAX_HOURS . ' часов.');
            }

            if ($this->isBusy($table->id, $start, $end)) {
                throw new BusinessException('Столик уже занят на выбранное время.');
            }

            if (! $actor->isAdmin() && $this->activeCount($actor) >= self::MAX_ACTIVE_BOOKINGS) {
                throw new BusinessException(
                    'Нельзя иметь больше ' . self::MAX_ACTIVE_BOOKINGS . ' активных броней одновременно.'
                );
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
                // если блюдо недоступно, исключение откатит и саму бронь
                $booking->dishes()->sync($this->preOrderPayload($data['dishes']));
            }

            return $booking;
        });

        Log::info('Создана бронь', [
            'booking_id' => $booking->id,
            'user_id' => $actor->id,
            'table_id' => $booking->table_id,
        ]);

        return $booking->load(self::RELATIONS);
    }

    public function cancel(User $actor, Booking $booking): Booking
    {
        $this->changeStatus($booking, BookingStatus::Cancelled, function () use ($actor, $booking) {
            if (! $actor->isAdmin() && now()->addHours(self::CANCEL_HOURS)->greaterThan($booking->starts_at)) {
                throw new BusinessException(
                    'Отменить бронь можно не позднее чем за ' . self::CANCEL_HOURS . ' часа до начала.'
                );
            }
        });

        return $booking->load(self::RELATIONS);
    }

    public function updateStatus(Booking $booking, string $status): Booking
    {
        $this->changeStatus($booking, BookingStatus::from($status));

        return $booking->load(self::RELATIONS);
    }

    /** Добавить блюдо в предзаказ (attach) */
    public function addDish(Booking $booking, array $data): Booking
    {
        $this->ensureEditable($booking);

        if ($booking->dishes()->whereKey($data['dish_id'])->exists()) {
            throw new BusinessException('Это блюдо уже есть в предзаказе.');
        }

        $booking->dishes()->attach($this->preOrderPayload([$data]));

        return $booking->load(self::RELATIONS);
    }

    /** Убрать блюдо из предзаказа (detach) */
    public function removeDish(Booking $booking, Dish $dish): void
    {
        $this->ensureEditable($booking);

        if (! $booking->dishes()->whereKey($dish->id)->exists()) {
            abort(404, 'Этого блюда нет в предзаказе.');
        }

        $booking->dishes()->detach($dish->id);
    }

    /** Смена статуса только по разрешённым в BookingStatus переходам */
    private function changeStatus(Booking $booking, BookingStatus $to, ?callable $extraCheck = null): void
    {
        DB::transaction(function () use ($booking, $to, $extraCheck) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $from = $booking->status;

            if (! $from->canTransitionTo($to)) {
                Log::warning('Недопустимая смена статуса брони', [
                    'booking_id' => $booking->id,
                    'from' => $from->value,
                    'to' => $to->value,
                ]);

                throw new BusinessException(
                    'Нельзя перевести бронь из статуса «' . $from->label() . '» в «' . $to->label() . '».'
                );
            }

            if ($extraCheck) {
                $extraCheck();
            }

            $booking->update(['status' => $to]);

            Log::info('Изменён статус брони', [
                'booking_id' => $booking->id,
                'from' => $from->value,
                'to' => $to->value,
            ]);
        });

        $booking->refresh();
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

    private function activeCount(User $user): int
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', BookingStatus::active())
            ->where('starts_at', '>', now())
            ->count();
    }

    /** Готовит данные для attach/sync: [dish_id => [quantity, price]] */
    private function preOrderPayload(array $items): array
    {
        $dishes = Dish::whereIn('id', collect($items)->pluck('dish_id'))->get()->keyBy('id');
        $payload = [];

        foreach ($items as $item) {
            $dish = $dishes[$item['dish_id']];

            if (! $dish->is_available) {
                throw new BusinessException('Блюдо «' . $dish->name . '» сейчас недоступно.');
            }

            $payload[$dish->id] = [
                'quantity' => $item['quantity'],
                'price' => $dish->price, // цена фиксируется на момент заказа
            ];
        }

        return $payload;
    }

    private function ensureEditable(Booking $booking): void
    {
        if (! in_array($booking->status->value, BookingStatus::active(), true)) {
            throw new BusinessException('Бронь «' . $booking->status->label() . '», изменять предзаказ нельзя.');
        }
    }
}
