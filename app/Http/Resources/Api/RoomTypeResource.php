<?php

namespace App\Http\Resources\Api;

use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RoomType
 */
class RoomTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'max_occupancy' => $this->max_occupancy,
            'total_rooms_available' => $this->whenCounted('rooms'),
            'available_rooms_count' => $this->when(
                $request->has(['check_in', 'check_out']),
                fn () => $this->availableRoomsCount($request->input('check_in'), $request->input('check_out'))
            ),
        ];
    }
}
