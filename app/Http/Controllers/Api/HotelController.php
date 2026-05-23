<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $hotels = Hotel::query()
            ->with([
                'destination',
                'cancellationPolicy',
            ])
            ->where('status', 'active')
            ->filter($request->only(['q', 'destination']))
            ->latest()
            ->paginate(10);

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
                'hotelSetting'
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
