<?php

namespace Database\Factories;

use App\Models\Hall;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Table>
 */
class TableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'number' => fake()->numberBetween(1, 50),
            'seats' => fake()->randomElement([2, 4, 6, 8]),
            'is_active' => true,
        ];
    }
}
