<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OccupancyRequest;
use App\Http\Requests\StoreHallRequest;
use App\Http\Requests\UpdateHallRequest;
use App\Http\Resources\HallResource;
use App\Http\Resources\TableResource;
use App\Models\Hall;
use App\Services\HallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class HallController extends Controller
{
    public function __construct(private HallService $halls)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return HallResource::collection(
            $this->halls->paginate($request->only('is_active'))
        );
    }

    public function store(StoreHallRequest $request): JsonResponse
    {
        $hall = $this->halls->create($request->validated());

        return (new HallResource($hall))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Hall $hall): HallResource
    {
        return new HallResource($hall);
    }

    public function update(UpdateHallRequest $request, Hall $hall): HallResource
    {
        return new HallResource(
            $this->halls->update($hall, $request->validated())
        );
    }

    public function destroy(Hall $hall): Response
    {
        $this->halls->delete($hall);

        return response()->noContent();
    }

    /** Вложенный ресурс: GET /api/v1/halls/{hall}/tables */
    public function tables(Hall $hall): AnonymousResourceCollection
    {
        return TableResource::collection($this->halls->tables($hall));
    }

    /** Схема занятости зала на день: GET /api/v1/halls/{hall}/occupancy?date=2026-11-20 */
    public function occupancy(OccupancyRequest $request, Hall $hall): AnonymousResourceCollection
    {
        return TableResource::collection(
            $this->halls->occupancy($hall, $request->validated('date'))
        );
    }
}