<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['hotel_id', 'checkin_time', 'checkout_time', 'platform_commission'])]
class HotelSetting extends Model
{
    use SoftDeletes;
    public function hotel():belongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
