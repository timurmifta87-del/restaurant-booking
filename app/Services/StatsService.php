<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hall;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /** Сводка по броням за период: статусы, залы, популярные блюда, сумма предзаказов */
    public function summary(?string $from, ?string $to): array
    {
        $period = fn ($query, string $column = 'starts_at') => $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn ($q) => $q->whereDate($column, '<=', $to));

        // количество броней по статусам (groupBy)
        $byStatus = $period(Booking::query())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = collect(BookingStatus::cases())->map(fn (BookingStatus $status) => [
            'status' => $status->value,
            'label' => $status->label(),
            'total' => (int) ($byStatus[$status->value] ?? 0),
        ]);

        // загрузка залов (withCount по броням через столики)
        $halls = Hall::query()
            ->withCount(['tables', 'bookings' => fn ($q) => $period($q, 'bookings.starts_at')
                ->whereIn('bookings.status', BookingStatus::active())])
            ->orderByDesc('bookings_count')
            ->get()
            ->map(fn (Hall $hall) => [
                'id' => $hall->id,
                'name' => $hall->name,
                'tables_count' => $hall->tables_count,
                'bookings_count' => $hall->bookings_count,
            ]);

        // предзаказы: только не отменённые брони
        $preorders = $period(
            DB::table('booking_dish')
                ->join('bookings', 'bookings.id', '=', 'booking_dish.booking_id')
                ->where('bookings.status', '!=', BookingStatus::Cancelled->value),
            'bookings.starts_at'
        );

        $topDishes = (clone $preorders)
            ->join('dishes', 'dishes.id', '=', 'booking_dish.dish_id')
            ->selectRaw('dishes.id, dishes.name, SUM(booking_dish.quantity) as portions, SUM(booking_dish.quantity * booking_dish.price) as revenue')
            ->groupBy('dishes.id', 'dishes.name')
            ->orderByDesc('portions')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'portions' => (int) $row->portions,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $revenue = (float) (clone $preorders)->sum(DB::raw('booking_dish.quantity * booking_dish.price'));

        return [
            'period' => ['from' => $from, 'to' => $to],
            'bookings_total' => $statuses->sum('total'),
            'by_status' => $statuses->values(),
            'halls' => $halls->values(),
            'top_dishes' => $topDishes->values(),
            'preorder_revenue' => round($revenue, 2),
        ];
    }
}
