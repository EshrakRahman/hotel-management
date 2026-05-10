<?php

namespace App\Models;

use App\Enums\HotelStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'destination_id', 'cancellation_policy_id', 'name', 'slug', 'address', 'latitude', 'longitude', 'status'])]
class Hotel extends Model
{
    use SoftDeletes;

    protected function casts():array
    {
        return [
            'status' => HotelStatus::class,
        ];
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function destination():BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function cancellationPolicy():BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    public function roomTypes():hasMany
    {
        return $this->hasMany(RoomType::class);
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

        return $query;
    }}
