<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingDishRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Dish;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BookingDishController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    /** POST /api/v1/bookings/{booking}/dishes */
    public function store(StoreBookingDishRequest $request, Booking $booking): JsonResponse
    {
        Gate::authorize('update', $booking);

        $booking = $this->bookings->addDish($booking, $request->validated());

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    /** DELETE /api/v1/bookings/{booking}/dishes/{dish} */
    public function destroy(Booking $booking, Dish $dish): Response
    {
        Gate::authorize('update', $booking);

        $this->bookings->removeDish($booking, $dish);

        return response()->noContent();
    }
}
