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
                'cancellationPolicy'
            ])
            ->where('status', 'active')
            ->filter($request->only(['q', 'destination']))
            ->latest()
            ->paginate(10);

        return HotelResource::collection($hotels);
    }

    public function show(string $slug)
    {
        $hotel = Hotel::where('slug', $slug)
            ->where('status', 'active')
            ->with(['destination', 'cancellationPolicy', 'roomTypes'])
            ->firstOrFail();

        return new HotelResource($hotel);
    }
}
