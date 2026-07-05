<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CalculateQuoteRequest;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Http\Resources\Api\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    /**
     * Generate an invoice quote with real-time price calculations before booking.
     */
    public function quote(CalculateQuoteRequest $request, BookingService $bookingService): JsonResponse
    {
        $quote = $bookingService->getQuote($request->validated());

        return response()->json($quote);
    }

    /**
     * Display a listing of bookings.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Booking::query();

        if ($user->hasRole('admin')) {
            // Admin can see everything
        } elseif ($user->hasRole('hotel_owner')) {
            // Hotel owners see bookings for their hotels
            $query->whereHas('hotel', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } else {
            // Default: Customers only see their own bookings
            $query->where('user_id', $user->id);
        }

        $query->when($request->input('status'), function ($q, $status) {
            $q->where('status', $status);
        });

        $bookings = $query->with([
            'bookingItems.roomType',
            'bookingItems.room',
            'bookingGuests',
            'bookingServices.serviceable',
            'hotel',
        ])
            ->latest()
            ->paginate(15);

        return BookingResource::collection($bookings);
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(StoreBookingRequest $request, BookingService $bookingService): BookingResource
    {
        $booking = $bookingService->createBooking(auth()->user(), $request->validated());

        return new BookingResource($booking);
    }

    /**
     * Display the specified reservation by booking reference.
     */
    public function show(Booking $booking): BookingResource
    {
        Gate::authorize('view', $booking);

        $booking->load(['bookingItems.roomType', 'bookingItems.room', 'bookingGuests', 'bookingServices.serviceable', 'hotel']);

        return new BookingResource($booking);
    }

    /**
     * Cancel the specified reservation by booking reference.
     */
    public function cancel(Booking $booking, BookingService $bookingService): BookingResource
    {
        Gate::authorize('cancel', $booking);

        $booking->load(['bookingItems.roomType', 'bookingItems.room', 'bookingGuests', 'bookingServices.serviceable', 'hotel']);

        $booking = $bookingService->cancelBooking($booking);

        return new BookingResource($booking);
    }
}
