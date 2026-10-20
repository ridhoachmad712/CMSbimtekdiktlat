<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\HasSlug;
use App\Support\ArticleHtml;
use App\Support\ImageVariants;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Post extends Model
{
    use HasSlug;

    protected $fillable = [
        'category_id', 'user_id', 'title', 'slug', 'excerpt', 'content', 'image',
        'published_at', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Gambar utama yang diunggah lewat admin langsung dibuatkan versi kecil.
        static::saved(function (Post $post) {
            if ($post->image && ($post->wasRecentlyCreated || $post->wasChanged('image'))) {
                ImageVariants::generate($post->image);
            }
        });
    }

    /** Atribut <img> responsif untuk gambar utama (src kecil + srcset + dimensi). */
    public function imageAttributes(string $sizes = '100vw'): ?array
    {
        if (! $this->image) {
            return null;
        }
        $meta = ImageVariants::meta($this->image) ?? ['src' => $this->image_url, 'srcset' => null, 'width' => null, 'height' => null];

        return array_filter([
            'src' => $meta['src'],
            'srcset' => $meta['srcset'],
            'sizes' => $meta['srcset'] ? $sizes : null,
            'width' => $meta['width'],
            'height' => $meta['height'],
        ]);
    }

    public function slugSource(): string
    {
        return 'title';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Isi artikel siap tampil + daftar isi (dari <h2>).
     *
     * @return array{html: string, toc: array<int, array{id: string, text: string}>}
     */
    public function renderedContent(): array
    {
        $html = RichContentRenderer::make($this->content ?? '')
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->toHtml();

        return ArticleHtml::process($html, $this->title);
    }

    /** URL absolut gambar utama (disk public), null bila tidak ada. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at');
    }

    /** URL publik bila sudah terbit; selain itu tautan pratinjau bertanda tangan (30 menit). */
    public function publicUrl(): string
    {
        return $this->status === PostStatus::Published
            ? route('posts.show', $this)
            : URL::temporarySignedRoute('posts.preview', now()->addMinutes(30), ['post' => $this->id]);
    }

    public function getStatusAttribute(): PostStatus
    {
        return match (true) {
            $this->published_at === null => PostStatus::Draft,
            $this->published_at->isFuture() => PostStatus::Scheduled,
            default => PostStatus::Published,
        };
    }

    /** Filter daftar artikel admin per status. */
    public function scopeWithStatus(Builder $query, PostStatus $status): void
    {
        match ($status) {
            PostStatus::Draft => $query->whereNull('published_at'),
            PostStatus::Scheduled => $query->where('published_at', '>', now()),
            PostStatus::Published => $query->where('published_at', '<=', now()),
        };
    }
}
