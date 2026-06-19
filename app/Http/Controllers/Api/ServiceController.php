<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ServiceResource;
use App\Models\Hotel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(string $hotelSlug): AnonymousResourceCollection
    {
        $hotel = Hotel::where('slug', $hotelSlug)
            ->where('status', 'active')
            ->firstOrFail();

        $services = $hotel->services()
            ->where('is_active', true)
            ->get();

        return ServiceResource::collection($services);
    }
}
