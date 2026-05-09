<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_featured'])]
class Destination extends Model
{
    public function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function hotels():hasMany
    {
        return $this->hasMany(Hotel::class);
    }
}
