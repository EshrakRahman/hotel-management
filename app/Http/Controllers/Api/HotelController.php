<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $hotelsQuery = Hotel::query()
            ->with([
                'destination',
                'cancellationPolicy',
            ])
            ->where('status', 'active')
            ->filter($request->only(['q', 'destination']));

        if ($request->has(['check_in', 'check_out'])) {
            $checkIn = $request->input('check_in');
            $checkOut = $request->input('check_out');

            // Filter hotels with at least one room type that has at least one vacant room
            $hotelsQuery->whereHas('roomTypes', function ($query) use ($checkIn, $checkOut) {
                $query->whereHas('rooms', function ($q) use ($checkIn, $checkOut) {
                    $q->where('status', RoomStatus::AVAILABLE)
                        ->whereDoesntHave('bookingItems', function ($bQuery) use ($checkIn, $checkOut) {
                            $bQuery->where('check_in', '<', $checkOut)
                                ->where('check_out', '>', $checkIn)
                                ->whereHas('booking', function ($b) {
                                    $b->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PENDING])
                                        ->where('created_at', '>=', now()->subMinutes(15));
                                });
                        });
                });
            });

            // Eager load room types with count of rooms so available count works without N+1
            $hotelsQuery->with(['roomTypes' => function ($query) {
                $query->withCount('rooms');
            }]);
        }

        $hotels = $hotelsQuery->latest()->paginate(10);

        return HotelResource::collection($hotels);
    }

    public function show(string $slug)
    {
        $hotel = Hotel::query()
            ->where([
                'slug' => $slug,
                'status' => 'active',
            ])
            ->with([
                'destination',
                'cancellationPolicy',
                'roomTypes' => function ($query) {
                    $query->withCount('rooms');
                },
                'amenities',
                'services',
                'hotelSetting',
            ])
            ->firstOrFail();

        return new HotelResource($hotel);
    }

    public function featured()
    {
        $featuredHotel = Hotel::query()
            ->where('status', 'active')
            ->inRandomOrder()
            ->take(5)
            ->with([
                'destination',
                'cancellationPolicy',
            ])
            ->get();

        return HotelResource::collection($featuredHotel);
    }
}
