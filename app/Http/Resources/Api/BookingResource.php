<?php

namespace App\Http\Resources\Api;

use App\Enums\PromotionsDiscountType;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
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
            'booking_ref' => $this->booking_ref,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'special_requests' => $this->special_request,
            'pricing' => [
                'room_subtotal' => number_format($this->bookingItems->sum('subtotal'), 2, '.', ''),
                'service_subtotal' => number_format($this->total_service_amount, 2, '.', ''),
                'discount_amount' => number_format($this->calculateDiscountAmount(), 2, '.', ''),
                'tax_amount' => number_format($this->tax_amount, 2, '.', ''),
                'platform_fee' => number_format($this->platform_fee, 2, '.', ''),
                'total_amount' => number_format($this->total_amount, 2, '.', ''),
            ],
            'hotel' => $this->relationLoaded('hotel') ? [
                'id' => $this->hotel->id,
                'name' => $this->hotel->name,
            ] : null,
            'items' => BookingItemResource::collection($this->whenLoaded('bookingItems')),
            'guests' => BookingGuestResource::collection($this->whenLoaded('bookingGuests')),
            'services' => BookingServiceResource::collection($this->whenLoaded('bookingServices')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    /**
     * Calculate discount helper for the resource
     */
    private function calculateDiscountAmount(): float
    {
        if (! $this->promotion_id) {
            return 0.00;
        }

        $roomSubtotal = $this->bookingItems->sum('subtotal');

        // Eager load promotion dynamically if needed, or if already loaded
        $promo = $this->relationLoaded('promotion') ? $this->promotion : $this->resource->promotion;

        if ($promo) {
            if ($promo->discount_type === PromotionsDiscountType::PERCENTAGE || $promo->discount_type->value === 'percentage') {
                return $roomSubtotal * ($promo->discount_value / 100);
            }

            return min($promo->discount_value, $roomSubtotal);
        }

        return 0.00;
    }
}
