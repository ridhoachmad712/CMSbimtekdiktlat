<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasSlug;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Hanya kategori yang punya minimal satu materi aktif (untuk tampilan publik). */
    public function scopeHasActiveTopics(Builder $query): void
    {
        $query->whereHas('topics', fn (Builder $q) => $q->where('is_active', true));
    }
}
