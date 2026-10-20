<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'price', 'description', 'facilities', 'min_participants', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['facilities' => 'array', 'is_active' => 'boolean', 'price' => 'integer'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Harga 0 = tidak ditampilkan (penawaran via WhatsApp). */
    public function hasPrice(): bool
    {
        return $this->price > 0;
    }

    /** Contoh: "Rp 5.000.000", atau "Hubungi kami" bila harga tidak ditampilkan. */
    public function getFormattedPriceAttribute(): string
    {
        return $this->hasPrice() ? 'Rp '.number_format($this->price, 0, ',', '.') : 'Hubungi kami';
    }
}
