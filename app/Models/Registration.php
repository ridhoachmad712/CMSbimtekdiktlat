<?php

namespace App\Models;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Services\RegistrationAlerts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Registration extends Model
{
    protected $fillable = [
        'code', 'schedule_id', 'topic_id', 'package_id', 'contact_name', 'phone', 'email',
        'institution', 'position', 'region', 'participants_count', 'notes', 'status', 'source',
    ];

    /** Samakan dengan default kolom agar status tersedia sebelum di-refresh. */
    protected $attributes = [
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'source' => RegistrationSource::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Registration $registration) {
            $registration->code ??= 'REG-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        });

        // Email/WA ke admin hanya untuk pendaftaran dari website (dicek di RegistrationAlerts).
        static::created(fn (Registration $registration) => app(RegistrationAlerts::class)->newRegistration($registration));
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}
