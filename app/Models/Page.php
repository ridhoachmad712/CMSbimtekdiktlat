<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasSlug;

    protected $fillable = ['title', 'slug', 'content', 'is_published', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function slugSource(): string
    {
        return 'title';
    }
}
