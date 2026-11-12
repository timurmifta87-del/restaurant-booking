<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDishRequest;
use App\Http\Requests\UpdateDishRequest;
use App\Http\Resources\DishResource;
use App\Models\Dish;
use App\Services\DishService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DishController extends Controller
{
    public function __construct(private DishService $dishes)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return DishResource::collection(
            $this->dishes->paginate(
                $request->only(['menu_category_id', 'search', 'is_available', 'sort', 'direction'])
            )
        );
    }

    public function store(StoreDishRequest $request): JsonResponse
    {
        $dish = $this->dishes->create($request->validated());

        return (new DishResource($dish))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Dish $dish): DishResource
    {
        return new DishResource($dish->load('category'));
    }

    public function update(UpdateDishRequest $request, Dish $dish): DishResource
    {
        return new DishResource(
            $this->dishes->update($dish, $request->validated())
        );
    }

    public function destroy(Dish $dish): Response
    {
        $this->dishes->delete($dish);

        return response()->noContent();
    }

    /** Нестандартное действие: PATCH /api/v1/dishes/{dish}/availability */
    public function toggleAvailability(Dish $dish): DishResource
    {
        return new DishResource($this->dishes->toggleAvailability($dish));
    }
}