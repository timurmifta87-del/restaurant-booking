<?php

namespace App\Services;

use App\Models\Dish;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class DishService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $sort = in_array($filters['sort'] ?? null, ['name', 'price'], true)
            ? $filters['sort']
            : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Dish::query()
            ->with('category')
            ->when(
                isset($filters['menu_category_id']),
                fn ($query) => $query->where('menu_category_id', $filters['menu_category_id'])
            )
            ->when(
                isset($filters['search']),
                fn ($query) => $query->where('name', 'like', '%' . $filters['search'] . '%')
            )
            ->when(
                isset($filters['is_available']),
                fn ($query) => $query->where(
                    'is_available',
                    filter_var($filters['is_available'], FILTER_VALIDATE_BOOLEAN)
                )
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): Dish
    {
        $data = $this->handleImage($data);

        return Dish::create($data)->load('category');
    }

    public function update(Dish $dish, array $data): Dish
    {
        if (isset($data['image']) && $dish->image_path) {
            Storage::disk('public')->delete($dish->image_path);
        }

        $data = $this->handleImage($data);
        $dish->update($data);

        return $dish->load('category');
    }

    public function toggleAvailability(Dish $dish): Dish
    {
        $dish->update(['is_available' => ! $dish->is_available]);

        return $dish->load('category');
    }

    public function delete(Dish $dish): void
    {
        if ($dish->bookings()->exists()) {
            abort(409, 'Нельзя удалить блюдо, которое есть в предзаказах. Отключите его в меню.');
        }

        if ($dish->image_path) {
            Storage::disk('public')->delete($dish->image_path);
        }

        $dish->delete();
    }

    private function handleImage(array $data): array
    {
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image_path'] = $data['image']->store('dishes', 'public');
        }

        unset($data['image']);

        return $data;
    }
}