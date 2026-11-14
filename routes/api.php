<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingDishController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TableController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
})->name('health');

// Аутентификация
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'me'])->name('user');
});

// API версии 1
Route::prefix('v1')->name('v1.')->group(function () {

    // Каталог: просмотр доступен всем
    Route::apiResource('menu-categories', MenuCategoryController::class)->only(['index', 'show']);
    Route::apiResource('dishes', DishController::class)->only(['index', 'show']);
    Route::apiResource('halls', HallController::class)->only(['index', 'show']);
    Route::get('halls/{hall}/tables', [HallController::class, 'tables'])->name('halls.tables');
    Route::get('tables/available', [TableController::class, 'available'])->name('tables.available');
    Route::apiResource('tables', TableController::class)->only(['index', 'show']);

    // Авторизованные пользователи: свои брони и предзаказ
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('bookings', BookingController::class)->only(['index', 'show', 'store']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('bookings/{booking}/dishes', [BookingDishController::class, 'store'])->name('bookings.dishes.store');
        Route::delete('bookings/{booking}/dishes/{dish}', [BookingDishController::class, 'destroy'])->name('bookings.dishes.destroy');
    });

    // Администратор: справочники, статусы броней, занятость залов, статистика
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::apiResource('menu-categories', MenuCategoryController::class)->except(['index', 'show']);
        Route::apiResource('dishes', DishController::class)->except(['index', 'show']);
        Route::patch('dishes/{dish}/availability', [DishController::class, 'toggleAvailability'])->name('dishes.availability');
        Route::apiResource('halls', HallController::class)->except(['index', 'show']);
        Route::get('halls/{hall}/occupancy', [HallController::class, 'occupancy'])->name('halls.occupancy');
        Route::apiResource('tables', TableController::class)->except(['index', 'show']);
        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
        Route::get('stats', StatsController::class)->name('stats');
    });
});
