<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingDishRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Dish;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookingDishController extends Controller
{
    use ResolvesActor;

    public function __construct(private BookingService $bookings)
    {
    }

    public function store(StoreBookingDishRequest $request, Booking $booking): JsonResponse
    {
        $booking = $this->bookings->addDish(
            $this->actor($request),
            $booking,
            $request->validated()
        );

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Booking $booking, Dish $dish): Response
    {
        $this->bookings->removeDish($this->actor($request), $booking, $dish);

        return response()->noContent();
    }
}