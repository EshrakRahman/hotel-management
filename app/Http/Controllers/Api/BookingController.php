<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CalculateQuoteRequest;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Http\Resources\Api\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function show(string $bookingRef): BookingResource
    {
        $booking = Booking::where('booking_ref', $bookingRef)
            ->with(['bookingItems.roomType', 'bookingItems.room', 'bookingGuests', 'bookingServices.serviceable', 'hotel'])
            ->firstOrFail();

        $this->authorizeAccess($booking);

        return new BookingResource($booking);
    }

    /**
     * Cancel the specified reservation by booking reference.
     */
    public function cancel(string $bookingRef, BookingService $bookingService): BookingResource
    {
        $booking = Booking::where('booking_ref', $bookingRef)
            ->with(['bookingItems.roomType', 'bookingItems.room', 'bookingGuests', 'bookingServices.serviceable', 'hotel'])
            ->firstOrFail();

        $this->authorizeAccess($booking);

        $booking = $bookingService->cancelBooking($booking);

        return new BookingResource($booking);
    }

    /**
     * Authorize that the current authenticated user can access the booking.
     *
     * @throws AuthorizationException
     */
    private function authorizeAccess(Booking $booking): void
    {
        $user = auth()->user();

        // Admin can access everything
        if ($user->hasRole('admin')) {
            return;
        }

        // Customer owns the booking
        if ($booking->user_id === $user->id) {
            return;
        }

        // Hotelier owns the hotel
        if ($booking->hotel->user_id === $user->id) {
            return;
        }

        abort(403, 'This action is unauthorized.');
    }
}
