<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    use ResolvesActor;

    public function __construct(private BookingService $bookings)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return BookingResource::collection(
            $this->bookings->paginate(
                $this->actor($request),
                $request->only(['date', 'status', 'hall_id'])
            )
        );
    }

    public function show(Request $request, Booking $booking): BookingResource
    {
        return new BookingResource(
            $this->bookings->find($this->actor($request), $booking)
        );
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['user_id']);

        $booking = $this->bookings->create($this->actor($request), $data);

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(Request $request, Booking $booking): BookingResource
    {
        return new BookingResource(
            $this->bookings->cancel($this->actor($request), $booking)
        );
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): BookingResource
    {
        return new BookingResource(
            $this->bookings->updateStatus(
                $this->actor($request),
                $booking,
                $request->validated('status')
            )
        );
    }
}