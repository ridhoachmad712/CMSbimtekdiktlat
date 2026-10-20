<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venue extends Model
{
    protected $fillable = ['city_id', 'name', 'address', 'map_url'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
