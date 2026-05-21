<?php

namespace App\Models;

use App\Enums\PromotionsDiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'promo_code', 'discount_type', 'discount_value', 'start_date', 'end_date', 'is_active'])]
class Promotion extends Model
{
    protected function casts():array
    {
        return [
            'is_active' => 'boolean',
            'discount_type' => PromotionsDiscountType::class,
        ];
    }

    public function bookings():hasMany
    {
        return $this->hasMany(Booking::class);
    }


}
