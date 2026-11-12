<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingDishController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\TableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    try {
        DB::select('select 1');
        $database = 'ok';
        $status = 200;
    } catch (\Throwable $e) {
        $database = 'error';
        $status = 503;
    }

    return response()->json([
        'app' => config('app.name'),
        'time' => now()->toIso8601String(),
        'database' => $database,
    ], $status);
});

// API версии 1: адреса вида /api/v1/...
// В ПР8 сюда добавятся middleware для авторизации и ролей.
Route::prefix('v1')->name('v1.')->group(function () {
    // Меню
    Route::apiResource('menu-categories', MenuCategoryController::class);
    Route::patch('dishes/{dish}/availability', [DishController::class, 'toggleAvailability'])
        ->name('dishes.availability');
    Route::apiResource('dishes', DishController::class);

    // Залы и столики
    Route::get('halls/{hall}/tables', [HallController::class, 'tables'])->name('halls.tables');
    Route::get('halls/{hall}/occupancy', [HallController::class, 'occupancy'])->name('halls.occupancy');
    Route::apiResource('halls', HallController::class);

    Route::get('tables/available', [TableController::class, 'available'])->name('tables.available');
    Route::apiResource('tables', TableController::class);

    // Брони и предзаказ
    Route::apiResource('bookings', BookingController::class)->only(['index', 'show', 'store']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
    Route::post('bookings/{booking}/dishes', [BookingDishController::class, 'store'])->name('bookings.dishes.store');
    Route::delete('bookings/{booking}/dishes/{dish}', [BookingDishController::class, 'destroy'])->name('bookings.dishes.destroy');
});