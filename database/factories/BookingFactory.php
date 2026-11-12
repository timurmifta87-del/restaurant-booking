<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::instance(fake()->dateTimeBetween('+1 day', '+30 days'))
            ->setMinutes(0)
            ->setSeconds(0);

        return [
            'user_id' => User::factory(),
            'table_id' => Table::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'guests_count' => fake()->numberBetween(1, 4),
            'status' => fake()->randomElement(BookingStatus::cases()),
            'comment' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }
}
