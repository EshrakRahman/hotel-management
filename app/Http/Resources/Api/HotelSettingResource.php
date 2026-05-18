<?php

namespace App\Http\Resources\Api;

use App\Models\HotelSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HotelSetting
 */
class HotelSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'hotel_id' => $this->hotel_id,
            'checkin_time' => $this->checkin_time,
            'checkout_time' => $this->checkout_time,
        ];
    }
}
