<?php

namespace App\Models;

use App\Enums\HotelStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'destination_id', 'cancellation_policy_id', 'name', 'slug', 'address', 'latitude', 'longitude', 'status'])]
class Hotel extends Model
{
    use SoftDeletes;

    public function casts():array
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
}
