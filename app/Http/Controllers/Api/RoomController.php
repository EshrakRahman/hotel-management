<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RoomType $roomType)
    {
        $rooms = $roomType->rooms()
            ->select('id', 'room_number' , 'status')
            ->latest()
            ->get();

        return response()->json($rooms);
    }


    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        return response()->json([
            'data' => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'status' => $room->status,
                'room_type' => [
                    'id' => $room->roomType->id,
                    'name' => $room->roomType->name,
                ],
            ]
        ]);
    }
}
