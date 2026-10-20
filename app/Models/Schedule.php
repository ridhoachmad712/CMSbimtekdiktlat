<?php

namespace App\Models;

use App\Enums\ScheduleMode;
use App\Enums\ScheduleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = ['city_id', 'venue_id', 'topic_id', 'start_date', 'end_date', 'mode', 'quota', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'mode' => ScheduleMode::class,
            'status' => ScheduleStatus::class,
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->whereDate('start_date', '>=', today())
            ->where('status', ScheduleStatus::Open)
            ->orderBy('start_date');
    }

    /** Contoh: "20 - 23 Oktober 2026" */
    public function getDateRangeAttribute(): string
    {
        $start = $this->start_date->locale('id');
        $end = $this->end_date->locale('id');

        return $start->isSameMonth($end)
            ? $start->format('d').' - '.$end->translatedFormat('d F Y')
            : $start->translatedFormat('d F').' - '.$end->translatedFormat('d F Y');
    }

    /** Contoh: "20 - 23 Oktober 2026 · Bali" */
    public function getLabelAttribute(): string
    {
        return $this->date_range.' · '.$this->city?->name;
    }
}
