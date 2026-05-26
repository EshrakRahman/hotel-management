<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['booking_id', 'serviceable_id', 'serviceable_type', 'price_at_booking', 'quantity'])]
class BookingService extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_at_booking' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function serviceable(): MorphTo
    {
        return $this->morphTo();
    }
}
