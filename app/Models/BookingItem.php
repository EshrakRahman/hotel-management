<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'room_type_id', 'room_id', 'check_in', 'check_out', 'price_at_booking', 'nights', 'subtotal'])]
class BookingItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'price_at_booking' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
