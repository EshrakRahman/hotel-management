<?php

namespace App\Http\Resources\Api;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Promotion
 */
class PromotionResource extends JsonResource
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
            'promo_code' => $this->promo_code,
            'discount_type' => $this->discount_type->value,
            'discount_value' => $this->discount_value,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
