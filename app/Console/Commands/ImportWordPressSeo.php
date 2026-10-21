<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\WordPress\SpamFilter;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Mengambil meta description & title SEO (Rank Math) dari halaman artikel WordPress lama
 * untuk artikel yang SUDAH ada di database ini (artikel spam di situs lama tidak tersentuh).
 * Deskripsi yang sudah diubah manual di admin tidak ditimpa, kecuali dengan --force.
 */
class ImportWordPressSeo extends Command
{
    protected $signature = 'wp:seo
        {--url=https://bimtekdiklat.com : Situs WordPress lama}
        {--limit= : Jumlah artikel maksimal (uji coba)}
        {--concurrency=5 : Permintaan paralel}
        {--force : Timpa juga meta yang sudah diubah manual}';

    protected $description = 'Impor meta description/title SEO (Rank Math) dari artikel WordPress lama';

    public function handle(): int
    {
        $base = rtrim($this->option('url'), '/');
        $posts = Post::whereNotNull('wp_slug')->orderBy('id')
            ->when($this->option('limit'), fn ($q, $limit) => $q->limit((int) $limit))
            ->get(['id', 'title', 'excerpt', 'wp_slug', 'meta_title', 'meta_description']);

        $stats = ['description' => 0, 'title' => 0, 'kept' => 0, 'failed' => 0, 'spam' => 0];
        $bar = $this->output->createProgressBar($posts->count());

        foreach ($posts->chunk(max(1, (int) $this->option('concurrency'))) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => $chunk->map(
                fn (Post $post) => $pool->as((string) $post->id)
                    ->withUserAgent('Mozilla/5.0 (compatible; CMS-Bimtek-Importer/1.0)')
                    ->timeout(30)
                    ->get($base.'/'.rawurlencode($post->wp_slug).'/')
            )->all());

            foreach ($chunk as $post) {
                $bar->advance();
                $response = $responses[(string) $post->id] ?? null;
                if (! $response instanceof Response || ! $response->successful()) {
                    $stats['failed']++;

                    continue;
                }

                [$title, $description] = $this->extract($response->body());
                if (SpamFilter::isSpam((string) $title, '', (string) $description)) {
                    $stats['spam']++;

                    continue;
                }

                $this->apply($post, $title, $description, $stats);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(['Hasil', 'Jumlah'], [
            ['Meta description diperbarui', $stats['description']],
            ['Meta title diperbarui', $stats['title']],
            ['Dipertahankan (sudah diubah manual)', $stats['kept']],
            ['Dilewati: terindikasi spam', $stats['spam']],
            ['Gagal diambil', $stats['failed']],
        ]);

        return self::SUCCESS;
    }

    /** @return array{0: string|null, 1: string|null} [judul SEO tanpa nama situs, meta description] */
    private function extract(string $html): array
    {
        $decode = fn (?string $s) => $s === null ? null : Str::squish(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        preg_match('~<meta\s+name="description"\s+content="([^"]*)"~i', $html, $d);
        preg_match('~<title>(.*?)</title>~is', $html, $t);
        preg_match('~<meta\s+property="og:site_name"\s+content="([^"]*)"~i', $html, $s);

        $title = $decode($t[1] ?? null);
        $site = $decode($s[1] ?? null);
        if ($title && $site) {
            $title = preg_replace('/\s*[-|–]\s*'.preg_quote($site, '/').'\s*$/u', '', $title);
        }

        return [$title ?: null, $decode($d[1] ?? null) ?: null];
    }

    private function apply(Post $post, ?string $title, ?string $description, array &$stats): void
    {
        $changes = [];
        // Deskripsi bawaan impor = ringkasan dipotong 155 karakter; selain itu berarti diubah manual.
        $auto = $post->excerpt ? Str::limit($post->excerpt, 155) : null;
        $editable = $this->option('force') || blank($post->meta_description) || $post->meta_description === $auto;

        if ($description && $description !== $post->meta_description) {
            if ($editable) {
                $changes['meta_description'] = Str::limit($description, 300, '');
                $stats['description']++;
            } else {
                $stats['kept']++;
            }
        }

        // Judul SEO hanya disimpan bila memang berbeda dari judul artikel.
        $normalize = fn (?string $s) => mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $s));
        if ($title && $normalize($title) !== $normalize($post->title) && ($this->option('force') || blank($post->meta_title))) {
            $changes['meta_title'] = Str::limit($title, 250, '');
            $stats['title']++;
        }

        if ($changes) {
            Post::whereKey($post->id)->update($changes);
        }
    }
}
