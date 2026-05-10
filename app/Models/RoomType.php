<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'slug', 'description', 'base_price', 'max_occupancy', 'hotel_id' ])]
class RoomType extends Model
{
    public function hotel():belongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
