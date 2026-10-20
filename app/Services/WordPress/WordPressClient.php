<?php

namespace App\Services\WordPress;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/** Pembaca REST API WordPress (wp-json/wp/v2), tanpa autentikasi. */
class WordPressClient
{
    public function __construct(private string $baseUrl, private int $downloadDelayMs = 0) {}

    public function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    public function host(): string
    {
        return preg_replace('/^www\./', '', (string) parse_url($this->baseUrl(), PHP_URL_HOST));
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    public function posts(int $page = 1, int $perPage = 20): array
    {
        $response = $this->request()->get('posts', [
            'page' => $page,
            'per_page' => $perPage,
            'orderby' => 'date',
            'order' => 'desc',
            '_embed' => 'wp:featuredmedia',
        ])->throw();

        return [
            'items' => $response->json() ?? [],
            'total' => (int) $response->header('X-WP-Total'),
            'totalPages' => (int) $response->header('X-WP-TotalPages'),
        ];
    }

    /** @return array<int, array<string, mixed>> keyed by WP category id */
    public function categories(): array
    {
        $items = $this->request()->get('categories', ['per_page' => 100])->throw()->json() ?? [];

        return collect($items)->keyBy('id')->all();
    }

    public function download(string $url): Response
    {
        // Jeda kecil + backoff agar server lama tidak memutus koneksi saat ribuan unduhan.
        Sleep::usleep($this->downloadDelayMs * 1000);

        return Http::withUserAgent($this->userAgent())->timeout(60)->retry([2000, 5000, 10000], throw: false)->get($url);
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl().'/wp-json/wp/v2/')
            ->withUserAgent($this->userAgent())
            ->acceptJson()
            ->timeout(60)
            ->retry(3, 2000);
    }

    private function userAgent(): string
    {
        return 'Mozilla/5.0 (compatible; CMS-Bimtek-Importer/1.0)';
    }
}
