<?php

namespace App\Http\Controllers\Api;

use App\Enums\HotelStatus;
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
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('status', HotelStatus::ACTIVE)
            ->filter($request->only(['q', 'destination']));

        if ($request->has(['check_in', 'check_out'])) {
            $checkIn = $request->input('check_in');
            $checkOut = $request->input('check_out');

            // Filter hotels with at least one room type that has at least one vacant room
            $hotelsQuery->availableBetween($checkIn, $checkOut);

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
                'status' => HotelStatus::ACTIVE,
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
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
            ->where('status', HotelStatus::ACTIVE)
            ->inRandomOrder()
            ->take(5)
            ->with([
                'destination',
                'cancellationPolicy',
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get();

        return HotelResource::collection($featuredHotel);
    }
}
