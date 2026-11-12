<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableTablesRequest;
use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Http\Resources\TableResource;
use App\Models\Table;
use App\Services\TableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TableController extends Controller
{
    public function __construct(private TableService $tables)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return TableResource::collection(
            $this->tables->paginate($request->only(['hall_id', 'min_seats']))
        );
    }

    public function store(StoreTableRequest $request): JsonResponse
    {
        $table = $this->tables->create($request->validated());

        return (new TableResource($table))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Table $table): TableResource
    {
        return new TableResource($table->load('hall'));
    }

    public function update(UpdateTableRequest $request, Table $table): TableResource
    {
        return new TableResource(
            $this->tables->update($table, $request->validated())
        );
    }

    public function destroy(Table $table): Response
    {
        $this->tables->delete($table);

        return response()->noContent();
    }

    /** GET /api/v1/tables/available?starts_at=...&ends_at=...&guests_count=4 */
    public function available(AvailableTablesRequest $request): AnonymousResourceCollection
    {
        return TableResource::collection(
            $this->tables->available($request->validated())
        );
    }
}