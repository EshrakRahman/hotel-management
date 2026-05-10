<?php

namespace App\Http\Resources\Api;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Hotel
 */
class HotelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'status' => $this->status,
            'destination' => new DestinationResource($this->whenLoaded('destination')) ,
            'room_types' => RoomTypeResource::collection($this->whenLoaded('roomTypes')),
            'cancellation_policy' => new CancellationPolicyResource($this->whenLoaded('cancellationPolicy')),
        ];
    }
}
