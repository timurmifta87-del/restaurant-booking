<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\MenuCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_category_id' => MenuCategory::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 150, 2500),
            'image_path' => null,
            'is_available' => true,
        ];
    }
}
