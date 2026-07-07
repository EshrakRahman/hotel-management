<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\HotelStatus;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'destination_id', 'cancellation_policy_id',
    'name', 'slug', 'address', 'latitude', 'longitude', 'status'])]
class Hotel extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => HotelStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function cancellationPolicy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'hotel_amenity', 'hotel_id', 'amenity_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);

    }

    public function hotelSetting(): HasOne
    {
        return $this->hasOne(HotelSetting::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // Search
        $query->when($filters['q'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%");
            });
        });

        // Destination filter
        $query->when($filters['destination'] ?? null, function ($query, $slug) {
            $query->whereHas('destination', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });
        });

        // Price filters
        $query->when($filters['min_price'] ?? null, function ($query, $minPrice) {
            $query->whereHas('roomTypes', function ($q) use ($minPrice) {
                $q->where('base_price', '>=', (float) $minPrice);
            });
        });

        $query->when($filters['max_price'] ?? null, function ($query, $maxPrice) {
            $query->whereHas('roomTypes', function ($q) use ($maxPrice) {
                $q->where('base_price', '<=', (float) $maxPrice);
            });
        });

        // Rating filter (average review rating >= rating)
        $query->when($filters['rating'] ?? null, function ($query, $rating) {
            $query->where(function ($q) use ($rating) {
                $q->whereRaw('(SELECT AVG(rating) FROM reviews WHERE reviews.hotel_id = hotels.id) >= CAST(? AS NUMERIC)', [(float) $rating]);
            });
        });

        // Sorting
        $query->when($filters['sort'] ?? null, function ($query, $sort) {
            if ($sort === 'price_asc') {
                $query->orderBy(
                    RoomType::select('base_price')
                        ->whereColumn('room_types.hotel_id', 'hotels.id')
                        ->orderBy('base_price', 'asc')
                        ->limit(1),
                    'asc'
                );
            } elseif ($sort === 'price_desc') {
                $query->orderBy(
                    RoomType::select('base_price')
                        ->whereColumn('room_types.hotel_id', 'hotels.id')
                        ->orderBy('base_price', 'desc')
                        ->limit(1),
                    'desc'
                );
            } elseif ($sort === 'rating_desc') {
                $query->orderBy(
                    Review::selectRaw('AVG(rating)')
                        ->whereColumn('reviews.hotel_id', 'hotels.id')
                        ->limit(1),
                    'desc'
                );
            }
        });

        return $query;
    }

    /**
     * Scope a query to only include hotels that have available rooms between the given dates.
     */
    public function scopeAvailableBetween(Builder $query, string $checkIn, string $checkOut): Builder
    {
        return $query->whereHas('roomTypes', function ($query) use ($checkIn, $checkOut) {
            $query->whereHas('rooms', function ($q) use ($checkIn, $checkOut) {
                $q->where('status', RoomStatus::AVAILABLE)
                    ->whereDoesntHave('bookingItems', function ($bQuery) use ($checkIn, $checkOut) {
                        $bQuery->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn)
                            ->whereHas('booking', function ($b) {
                                $b->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PENDING])
                                    ->where('created_at', '>=', now()->subMinutes(15));
                            });
                    });
            });
        });
    }
}
