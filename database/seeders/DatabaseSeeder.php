<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Dish;
use App\Models\Hall;
use App\Models\MenuCategory;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Пользователи: известные учётки для тестирования (пароль: password)
        User::factory()->admin()->create([
            'name' => 'Иванова Анна',
            'email' => 'admin@resto.test',
        ]);
        User::factory()->create([
            'name' => 'Тестовый Клиент',
            'email' => 'client@resto.test',
        ]);
        User::factory(8)->create();

        // Залы и столики
        $halls = [
            ['Основной зал', 'Главный зал на первом этаже', [2, 4, 4, 6]],
            ['Летняя терраса', 'Открытая площадка с видом на парк', [2, 4, 4]],
            ['VIP-зал', 'Закрытый зал для больших компаний', [8, 10]],
            ['Бар', 'Барная стойка и высокие столики', [2, 2, 2]],
            ['Каминный зал', 'Уютный зал с камином', [4, 6]],
        ];

        foreach ($halls as [$name, $description, $seatsList]) {
            $hall = Hall::factory()->create([
                'name' => $name,
                'description' => $description,
            ]);

            foreach ($seatsList as $i => $seats) {
                Table::factory()->create([
                    'hall_id' => $hall->id,
                    'number' => $i + 1,
                    'seats' => $seats,
                ]);
            }
        }

        // Меню: категории и блюда
        $menu = [
            'Закуски' => [
                ['Брускетта с томатами', 'Хлеб, томаты, базилик', 320],
                ['Тартар из говядины', 'Говядина, каперсы, желток', 690],
            ],
            'Салаты' => [
                ['Цезарь с курицей', 'Курица гриль, соус цезарь', 450],
                ['Греческий салат', 'Овощи, фета, маслины', 380],
            ],
            'Основные блюда' => [
                ['Стейк рибай', 'Мраморная говядина, 300 г', 1850],
                ['Паста карбонара', 'Спагетти, бекон, пармезан', 620],
                ['Лосось на гриле', 'Филе лосося с овощами', 1150],
            ],
            'Десерты' => [
                ['Тирамису', 'Классический десерт', 380],
                ['Чизкейк', 'Нью-Йорк с ягодным соусом', 350],
            ],
            'Напитки' => [
                ['Морс клюквенный', '0,3 л', 180],
                ['Лимонад домашний', '0,5 л', 220],
            ],
        ];

        $order = 1;
        foreach ($menu as $categoryName => $dishes) {
            $category = MenuCategory::factory()->create([
                'name' => $categoryName,
                'sort_order' => $order++,
            ]);

            foreach ($dishes as [$dishName, $description, $price]) {
                Dish::factory()->create([
                    'menu_category_id' => $category->id,
                    'name' => $dishName,
                    'description' => $description,
                    'price' => $price,
                ]);
            }
        }

        // Брони с предзаказом (связь «многие ко многим»).
        // Каждая бронь в свой день, поэтому пересечений по времени нет.
        $clients = User::where('role', UserRole::Client)->get();
        $tables = Table::all();
        $dishes = Dish::all();

        for ($i = 0; $i < 12; $i++) {
            $table = $tables[$i % $tables->count()];
            $start = now()->addDays($i + 1)->setTime(18, 0);

            $booking = Booking::factory()->create([
                'user_id' => $clients->random()->id,
                'table_id' => $table->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addHours(2),
                'guests_count' => min($table->seats, 2 + $i % 4),
                'status' => BookingStatus::cases()[$i % 3],
            ]);

            foreach ($dishes->random(rand(1, 3)) as $dish) {
                $booking->dishes()->attach($dish->id, [
                    'quantity' => rand(1, 3),
                    'price' => $dish->price,
                ]);
            }
        }
    }
}
