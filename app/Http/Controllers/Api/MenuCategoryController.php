<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuCategoryRequest;
use App\Http\Requests\UpdateMenuCategoryRequest;
use App\Http\Resources\MenuCategoryResource;
use App\Models\MenuCategory;
use App\Services\MenuCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MenuCategoryController extends Controller
{
    public function __construct(private MenuCategoryService $categories)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return MenuCategoryResource::collection($this->categories->allWithDishes());
    }

    public function store(StoreMenuCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->validated());

        return (new MenuCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(MenuCategory $menuCategory): MenuCategoryResource
    {
        return new MenuCategoryResource($menuCategory->load('dishes'));
    }

    public function update(UpdateMenuCategoryRequest $request, MenuCategory $menuCategory): MenuCategoryResource
    {
        return new MenuCategoryResource(
            $this->categories->update($menuCategory, $request->validated())
        );
    }

    public function destroy(MenuCategory $menuCategory): Response
    {
        $this->categories->delete($menuCategory);

        return response()->noContent();
    }
}