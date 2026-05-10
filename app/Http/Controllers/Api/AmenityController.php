<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AmenityResource;
use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function index()
    {
        $amenities = Amenity::all();

        return AmenityResource::collection($amenities);
    }
}
