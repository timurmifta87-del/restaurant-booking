<?php

namespace Database\Factories;

use App\Models\MenuCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuCategory>
 */
class MenuCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Категория ' . fake()->unique()->numberBetween(1, 999),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
