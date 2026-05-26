<?php

namespace App\Http\Resources\Api;

use App\Models\BookingItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BookingItem
 */
class BookingItemResource extends JsonResource
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
            'room_type' => $this->relationLoaded('roomType') ? [
                'id' => $this->roomType->id,
                'name' => $this->roomType->name,
                'slug' => $this->roomType->slug,
            ] : null,
            'room' => $this->relationLoaded('room') && $this->room ? [
                'id' => $this->room->id,
                'room_number' => $this->room->room_number,
            ] : null,
            'room_number' => $this->relationLoaded('room') && $this->room ? $this->room->room_number : null,
            'check_in' => $this->check_in->format('Y-m-d'),
            'check_out' => $this->check_out->format('Y-m-d'),
            'price_at_booking' => $this->price_at_booking,
            'nights' => $this->nights,
            'subtotal' => $this->subtotal,
        ];
    }
}
