<?php

namespace App\Services\WordPress;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Str;

/**
 * Mengimpor artikel WordPress (REST API) menjadi Post, lengkap dengan gambar.
 * Aman diulang: artikel dikenali lewat wp_id.
 */
class WordPressImporter
{
    public MediaDownloader $media;

    private ContentCleaner $cleaner;

    private ?CategoryMapper $categories = null;

    private ?int $authorId = null;

    /** @var array{created: int, updated: int, skipped: int} */
    public array $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

    public function __construct(
        private WordPressClient $client,
        private bool $dryRun = false,
        bool $downloadMedia = true,
        private bool $updateExisting = false,
    ) {
        $this->media = new MediaDownloader($client, $downloadMedia && ! $dryRun);
        $this->cleaner = new ContentCleaner($this->media, $client);
    }

    public function categoryMapper(): CategoryMapper
    {
        return $this->categories ??= new CategoryMapper($this->client->categories(), $this->dryRun);
    }

    /**
     * @param  Closure(string $status, array<string, mixed> $wp, ?Post $post): void|null  $onItem
     * @return int jumlah artikel yang diproses
     */
    public function run(?int $limit = null, int $perPage = 20, int $startPage = 1, ?Closure $onItem = null, ?Closure $onTotal = null): int
    {
        $processed = 0;
        $page = $startPage;

        do {
            $batch = $this->client->posts($page, $perPage);
            if ($page === $startPage && $onTotal) {
                $onTotal($batch['total']);
            }

            foreach ($batch['items'] as $wp) {
                if ($limit !== null && $processed >= $limit) {
                    return $processed;
                }
                [$status, $post] = $this->importOne($wp);
                $this->stats[$status]++;
                $processed++;
                $onItem && $onItem($status, $wp, $post);
            }

            $page++;
        } while ($page <= $batch['totalPages'] && $batch['items'] !== []);

        return $processed;
    }

    /** @return array{0: 'created'|'updated'|'skipped', 1: Post|null} */
    public function importOne(array $wp): array
    {
        $existing = Post::where('wp_id', $wp['id'])->first();
        if ($existing && ! $this->updateExisting) {
            return ['skipped', $existing];
        }

        $title = $this->text($wp['title']['rendered'] ?? '');
        $excerpt = $this->excerpt($wp['excerpt']['rendered'] ?? '');
        // Ringkasan WordPress sering diawali judul (dari <h1> di isi): buang agar tidak ganda.
        if ($title !== '' && Str::startsWith(Str::lower($excerpt), Str::lower($title))) {
            $excerpt = Str::ucfirst(ltrim(Str::after($excerpt, mb_substr($excerpt, 0, mb_strlen($title))), ' :–-'));
        }
        $category = $this->categoryMapper()->resolve($wp['categories'] ?? []);

        if ($this->dryRun) {
            return [$existing ? 'updated' : 'created', null];
        }

        $post = $existing ?? new Post;
        $post->forceFill([
            'wp_id' => $wp['id'],
            'wp_slug' => $wp['slug'],
            'slug' => $post->slug ?: $this->uniqueSlug(Str::slug(rawurldecode($wp['slug'])) ?: Str::slug($title), $post->id),
            'title' => Str::limit($title, 250, ''),
            'excerpt' => $excerpt ?: null,
            'content' => $this->cleaner->clean($wp['content']['rendered'] ?? '', $wp['link'] ?? null, $title),
            'image' => $this->featuredImage($wp) ?? $post->image,
            'category_id' => $category['id'],
            'user_id' => $post->user_id ?? $this->authorId(),
            'published_at' => CarbonImmutable::parse($wp['date']),
            'meta_description' => $excerpt ? Str::limit($excerpt, 155) : null,
        ])->save();

        return [$existing ? 'updated' : 'created', $post];
    }

    private function featuredImage(array $wp): ?string
    {
        $media = $wp['_embedded']['wp:featuredmedia'][0] ?? null;
        if (! $media || isset($media['code'])) {
            return null;
        }

        // Ukuran "large" (±1024px) cukup untuk kartu & header artikel.
        $url = $media['media_details']['sizes']['large']['source_url'] ?? $media['source_url'] ?? null;

        return $url && $this->media->isSourceUpload($url) ? $this->media->fetch($url) : null;
    }

    private function excerpt(string $html): string
    {
        $text = $this->text($html);
        $text = preg_replace('/\s*(\[(…|&hellip;|\.\.\.)\]|…|\.\.\.)\s*$/u', '', $text);

        return Str::limit($text, 300);
    }

    private function text(string $html): string
    {
        return Str::squish(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $slug = $base;
        $i = 2;
        while (Post::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function authorId(): ?int
    {
        return $this->authorId ??= User::where('role', UserRole::SuperAdmin)->value('id') ?? User::value('id');
    }
}
