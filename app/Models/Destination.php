<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description', 'is_featured'])]
class Destination extends Model
{
    public function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }
}
