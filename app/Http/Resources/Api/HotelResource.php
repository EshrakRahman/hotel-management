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
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'status' => $this->status,
            'destination' => new DestinationMiniResource($this->whenLoaded('destination')),
            'hotel_setting' => new HotelSettingResource($this->whenLoaded('hotelSetting')),
            'room_types' => RoomTypeResource::collection($this->whenLoaded('roomTypes')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'cancellation_policy' => new CancellationPolicyResource($this->whenLoaded('cancellationPolicy')),
            'average_rating' => isset($this->reviews_avg_rating) ? number_format((float) $this->reviews_avg_rating, 1, '.', '') : null,
            'reviews_count' => isset($this->reviews_count) ? (int) $this->reviews_count : 0,
        ];
    }
}
