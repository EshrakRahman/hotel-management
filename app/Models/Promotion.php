<?php

namespace App\Models;

use App\Enums\PromotionsDiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'promo_code', 'discount_type', 'discount_value', 'start_date', 'end_date', 'is_active'])]
class Promotion extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'discount_type' => PromotionsDiscountType::class,
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Calculate the discount amount based on the room subtotal.
     */
    public function calculateDiscount(float $roomSubtotal): float
    {
        if ($this->discount_type === PromotionsDiscountType::PERCENTAGE) {
            $discountAmount = $roomSubtotal * ($this->discount_value / 100);
        } else {
            $discountAmount = min($this->discount_value, $roomSubtotal);
        }

        return round($discountAmount, 2);
    }
}
