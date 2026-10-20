<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = ['registration_id', 'name', 'nip', 'position', 'phone', 'certificate_number'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
