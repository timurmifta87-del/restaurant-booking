<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Hall;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class HallService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Hall::query()
            ->withCount('tables')
            ->when(
                isset($filters['is_active']),
                fn ($query) => $query->where(
                    'is_active',
                    filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)
                )
            )
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function create(array $data): Hall
    {
        return Hall::create($data);
    }

    public function update(Hall $hall, array $data): Hall
    {
        $hall->update($data);

        return $hall->refresh();
    }

    public function delete(Hall $hall): void
    {
        if ($hall->tables()->exists()) {
            abort(409, 'Нельзя удалить зал, в котором есть столики.');
        }

        $hall->delete();
    }

    public function tables(Hall $hall): Collection
    {
        return $hall->tables()->orderBy('number')->get();
    }

    /** Схема занятости зала: столики с бронями на выбранный день */
    public function occupancy(Hall $hall, string $date): Collection
    {
        return $hall->tables()
            ->with(['bookings' => fn ($query) => $query
                ->whereDate('starts_at', $date)
                ->whereIn('status', BookingStatus::active())
                ->with('user')
                ->orderBy('starts_at')])
            ->orderBy('number')
            ->get();
    }
}