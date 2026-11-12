<?php

namespace Database\Factories;

use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hall>
 */
class HallFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Зал ' . fake()->unique()->numberBetween(1, 999),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
