<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingFilterRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    /** GET /api/v1/bookings?status=&hall_id=&date_from=&date_to=&search=&sort=&order=&per_page= */
    public function index(BookingFilterRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        return BookingResource::collection(
            $this->bookings->paginate($request->user(), $request->validated())
        );
    }

    public function show(Booking $booking): BookingResource
    {
        Gate::authorize('view', $booking);

        return new BookingResource($this->bookings->load($booking));
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        Gate::authorize('create', Booking::class);

        $booking = $this->bookings->create($request->user(), $request->validated());

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    /** POST /api/v1/bookings/{booking}/cancel */
    public function cancel(Request $request, Booking $booking): BookingResource
    {
        Gate::authorize('update', $booking);

        return new BookingResource($this->bookings->cancel($request->user(), $booking));
    }

    /** PATCH /api/v1/bookings/{booking}/status — только администратор */
    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): BookingResource
    {
        Gate::authorize('changeStatus', $booking);

        return new BookingResource(
            $this->bookings->updateStatus($booking, $request->validated('status'))
        );
    }
}
