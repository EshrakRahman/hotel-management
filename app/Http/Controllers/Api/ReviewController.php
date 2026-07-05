<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReviewRequest;
use App\Http\Resources\Api\ReviewResource;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Display a listing of the hotel's reviews.
     */
    public function index(string $hotelSlug)
    {
        $hotel = Hotel::where('slug', $hotelSlug)->firstOrFail();

        $reviews = $hotel->reviews()
            ->with('user')
            ->latest()
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review in database.
     *
     * @throws ValidationException
     */
    public function store(StoreReviewRequest $request, Booking $booking): ReviewResource
    {
        Gate::authorize('review', $booking);

        // 2. Booking Status: Must be confirmed or completed
        if (! in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])) {
            throw ValidationException::withMessages([
                'booking' => 'Only confirmed or completed bookings can be reviewed.',
            ]);
        }

        // 3. Past Stay: Checkout date must have passed
        $latestItem = $booking->bookingItems()->orderBy('check_out', 'desc')->first();
        if ($latestItem && Carbon::parse($latestItem->check_out)->isFuture()) {
            throw ValidationException::withMessages([
                'booking' => 'You cannot review a hotel before your stay has ended.',
            ]);
        }

        // 4. Uniqueness: Booking can only be reviewed once
        if ($booking->review()->exists()) {
            throw ValidationException::withMessages([
                'booking' => 'You have already submitted a review for this booking.',
            ]);
        }

        $validated = $request->validated();

        $review = Review::create([
            'user_id' => auth()->id(),
            'hotel_id' => $booking->hotel_id,
            'booking_id' => $booking->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return new ReviewResource($review->load('user'));
    }
}
