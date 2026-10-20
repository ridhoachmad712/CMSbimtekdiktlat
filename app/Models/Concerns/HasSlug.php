<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Isi kolom `slug` otomatis dari kolom sumber (default `name`) bila kosong.
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if (blank($model->slug)) {
                $model->slug = static::uniqueSlug(Str::slug($model->{$model->slugSource()}), $model->getKey());
            }
        });
    }

    public function slugSource(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function uniqueSlug(string $base, mixed $ignoreId = null): string
    {
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
