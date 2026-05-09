<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DestinationResource;
use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $query = Destination::query();

        // Search
        if ($request->filled('q')) {
            $query = $query->where('name', 'LIKE', '%' . $request->q . '%');
        }

        $destinations = $query
            ->latest()
            ->paginate(10);

        return DestinationResource::collection($destinations);
    }
}
