<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatsRequest;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(private StatsService $stats)
    {
    }

    /** GET /api/v1/stats?date_from=2026-11-01&date_to=2026-11-30 — только администратор */
    public function __invoke(StatsRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->summary(
                $request->validated('date_from'),
                $request->validated('date_to')
            ),
        ]);
    }
}
