<?php

namespace App\Services;

use App\Models\MenuCategory;
use Illuminate\Database\Eloquent\Collection;

class MenuCategoryService
{
    public function allWithDishes(): Collection
    {
        return MenuCategory::query()
            ->with(['dishes' => fn ($query) => $query->where('is_available', true)])
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): MenuCategory
    {
        return MenuCategory::create($data);
    }

    public function update(MenuCategory $category, array $data): MenuCategory
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(MenuCategory $category): void
    {
        if ($category->dishes()->exists()) {
            abort(409, 'Нельзя удалить категорию, в которой есть блюда.');
        }

        $category->delete();
    }
}