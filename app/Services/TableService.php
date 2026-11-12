<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TableService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Table::query()
            ->with('hall')
            ->when(isset($filters['hall_id']), fn ($query) => $query->where('hall_id', $filters['hall_id']))
            ->when(isset($filters['min_seats']), fn ($query) => $query->where('seats', '>=', $filters['min_seats']))
            ->orderBy('hall_id')
            ->orderBy('number')
            ->paginate($perPage);
    }

    public function create(array $data): Table
    {
        return Table::create($data)->load('hall');
    }

    public function update(Table $table, array $data): Table
    {
        $table->update($data);

        return $table->load('hall');
    }

    public function delete(Table $table): void
    {
        if ($table->bookings()->exists()) {
            abort(409, 'Нельзя удалить столик, для которого есть брони.');
        }

        $table->delete();
    }

    /** Свободные столики на выбранное время (поиск для ключевого сценария) */
    public function available(array $filters): Collection
    {
        $start = Carbon::parse($filters['starts_at']);
        $end = Carbon::parse($filters['ends_at']);

        return Table::query()
            ->with('hall')
            ->where('is_active', true)
            ->where('seats', '>=', $filters['guests_count'])
            ->whereHas('hall', fn ($query) => $query->where('is_active', true))
            ->whereDoesntHave('bookings', fn ($query) => $query
                ->whereIn('status', BookingStatus::active())
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start))
            ->when(isset($filters['hall_id']), fn ($query) => $query->where('hall_id', $filters['hall_id']))
            ->orderBy('hall_id')
            ->orderBy('number')
            ->get();
    }
}