<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'icon'])]
class Amenity extends Model
{
    public function hotels():BelongsToMany
    {
        return $this->belongsToMany(Hotel::class);
    }
}
