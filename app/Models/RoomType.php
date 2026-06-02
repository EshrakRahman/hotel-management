<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'base_price', 'max_occupancy', 'hotel_id'])]
class RoomType extends Model
{
    use HasFactory;

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Calculate available rooms of this type for the specified date range.
     */
    public function availableRoomsCount(string $checkIn, string $checkOut): int
    {
        $bookedRoomIds = BookingItem::where('room_type_id', $this->id)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PENDING])
                    ->where('created_at', '>=', now()->subMinutes(15));
            })
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->toArray();

        return $this->rooms()
            ->where('status', RoomStatus::AVAILABLE)
            ->whereNotIn('id', $bookedRoomIds)
            ->count();
    }
}
