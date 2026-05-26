<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\paymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'hotel_id', 'promotion_id', 'total_amount', 'total_service_amount',
    'tax_amount', 'platform_fee', 'cancellation_penalty', 'status', 'booking_ref',
    'payment_status', 'payment_method', 'special_request',
])]
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'payment_status' => paymentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function bookingGuests(): HasMany
    {
        return $this->hasMany(BookingGuest::class);
    }

    public function bookingServices(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }
}
