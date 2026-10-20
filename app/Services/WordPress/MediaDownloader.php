<?php

namespace App\Services\WordPress;

use App\Support\ImageVariants;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mengunduh berkas wp-content/uploads ke disk public (folder wp/...),
 * mempertahankan struktur tahun/bulan agar impor ulang tidak mengunduh ulang.
 */
class MediaDownloader
{
    public const MAX_BYTES = 20 * 1024 * 1024;

    private const ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'];

    public int $downloaded = 0;

    public int $reused = 0;

    /** @var array<int, string> */
    public array $failed = [];

    public function __construct(private WordPressClient $client, private bool $enabled = true) {}

    /** Apakah URL menunjuk ke berkas upload di situs WordPress sumber. */
    public function isSourceUpload(string $url): bool
    {
        $host = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));

        return $host === $this->client->host() && str_contains((string) parse_url($url, PHP_URL_PATH), '/wp-content/uploads/');
    }

    /** Path relatif pada disk public, mis. wp/2026/09/foto.webp. */
    public function targetPath(string $url): ?string
    {
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        $relative = Str::after($path, '/wp-content/uploads/');
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

        if ($relative === $path || ! in_array($extension, self::ALLOWED, true)) {
            return null;
        }

        $segments = array_map(fn ($s) => Str::slug(pathinfo($s, PATHINFO_FILENAME)) ?: 'file', explode('/', $relative));
        $segments[array_key_last($segments)] .= '.'.$extension;

        return 'wp/'.implode('/', $segments);
    }

    /** Unduh (atau pakai ulang) berkas; mengembalikan path disk public atau null bila gagal. */
    public function fetch(string $url): ?string
    {
        $path = $this->targetPath($url);
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $this->reused++;

            return $path;
        }

        if (! $this->enabled) {
            return null;
        }

        try {
            $response = $this->client->download($url);
        } catch (Throwable) {
            // Satu berkas gagal (mis. koneksi diputus server) tidak boleh menghentikan impor.
            $this->failed[] = $url;

            return null;
        }
        $body = $response->body();
        if (! $response->successful() || $body === '' || strlen($body) > self::MAX_BYTES || str_contains((string) $response->header('Content-Type'), 'text/html')) {
            $this->failed[] = $url;

            return null;
        }

        $disk->put($path, $body);
        ImageVariants::generate($path);
        $this->downloaded++;

        return $path;
    }

    /** URL publik relatif (tidak bergantung domain), mis. /storage/wp/2026/09/foto.webp. */
    public static function publicPath(string $path): string
    {
        return '/storage/'.$path;
    }
}
