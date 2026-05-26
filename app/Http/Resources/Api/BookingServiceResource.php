<?php

namespace App\Http\Resources\Api;

use App\Models\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BookingService
 */
class BookingServiceResource extends JsonResource
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
            'serviceable_type' => $this->serviceable_type,
            'serviceable_id' => $this->serviceable_id,
            'price_at_booking' => $this->price_at_booking,
            'quantity' => $this->quantity,
            'service' => $this->relationLoaded('serviceable') ? [
                'name' => $this->serviceable->name,
                'slug' => $this->serviceable->slug,
            ] : null,
        ];
    }
}
