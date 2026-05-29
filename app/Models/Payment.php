<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['booking_id', 'transaction_id', 'gateway', 'payload', 'amount', 'status'])]
class Payment extends Model
{
    use SoftDeletes;

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
