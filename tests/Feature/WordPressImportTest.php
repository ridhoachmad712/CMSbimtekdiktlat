<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class WordPressImportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const BASE = 'https://lama.test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        Sleep::fake();
    }

    protected function tearDown(): void
    {
        // Buang baris uji (lama.test) dari log gagal unduh; baris impor asli dibiarkan.
        $log = storage_path('logs/wp-import-failed.txt');
        if (is_file($log)) {
            $real = array_filter(file($log, FILE_IGNORE_NEW_LINES), fn ($line) => ! str_contains($line, 'lama.test'));
            $real ? file_put_contents($log, implode(PHP_EOL, $real).PHP_EOL) : unlink($log);
        }

        parent::tearDown();
    }

    private function wpPost(int $id, string $slug, array $categories, array $overrides = []): array
    {
        $up = self::BASE.'/wp-content/uploads/2026/09';

        return array_replace_recursive([
            'id' => $id,
            'date' => '2026-09-28T13:15:34',
            'slug' => $slug,
            'link' => self::BASE."/{$slug}/",
            'title' => ['rendered' => 'Pelatihan &#8211; Aplikasi Dispakati &amp; SKP'],
            'excerpt' => ['rendered' => '<p>Ringkasan pelatihan Dispakati untuk ASN [&hellip;]</p>'],
            'content' => ['rendered' => <<<HTML
                <div id="ez-toc-container" class="ez-toc-v2"><p class="ez-toc-title">Table of Contents</p><nav><ul><li><a href="{$this->base()}/{$slug}/#Tujuan">Tujuan</a></li></ul></nav></div>
                <h2><span class="ez-toc-section" id="Tujuan"></span>Tujuan Pelatihan<span class="ez-toc-section-end"></span></h2>
                <p style="text-align:justify">Lihat <a href="https://www.lama.test/artikel-lain/">artikel lain</a>, <a href="https://dispakati.bkn.go.id/">BKN</a> dan <a href="{$up}/modul.pdf">modul</a>.</p>
                <p>&nbsp;</p>
                <figure class="wp-block-image size-large"><img loading="lazy" width="1024" height="768" src="{$up}/foto-kegiatan-1024x768.webp" srcset="{$up}/foto-kegiatan.webp 1280w" alt="Foto &amp; kegiatan" class="wp-image-1"><figcaption class="wp-element-caption">Suasana kelas</figcaption></figure>
                <p><iframe src="https://www.youtube.com/embed/abcDEF12345"></iframe></p>
                <script>alert(1)</script>
                [caption id="x"]teks[/caption]
                HTML],
            'categories' => $categories,
            '_embedded' => ['wp:featuredmedia' => [[
                'source_url' => "{$up}/10.jpg",
                'media_details' => ['sizes' => ['large' => ['source_url' => "{$up}/10-1024x640.jpg"]]],
            ]]],
        ], $overrides);
    }

    private function base(): string
    {
        return self::BASE;
    }

    private function fakeWordPress(array $posts): void
    {
        Http::fake([
            self::BASE.'/wp-json/wp/v2/categories*' => Http::response([
                ['id' => 1, 'slug' => 'uncategorized', 'name' => 'Uncategorized', 'count' => 8],
                ['id' => 2, 'slug' => 'materi-bimtek-pilihan', 'name' => 'Materi Bimtek Pilihan', 'count' => 158],
                ['id' => 3, 'slug' => 'bimtek-kepegawaian', 'name' => 'Bimtek Kepegawaian', 'count' => 69],
                ['id' => 4, 'slug' => 'bimtek-gis', 'name' => 'Bimtek GIS', 'count' => 17],
                ['id' => 5, 'slug' => 'training-swasta', 'name' => 'Training Swasta', 'count' => 32],
            ]),
            self::BASE.'/wp-json/wp/v2/posts*' => Http::response($posts, 200, ['X-WP-Total' => count($posts), 'X-WP-TotalPages' => 1]),
            self::BASE.'/wp-content/uploads/*' => Http::response('binary-image', 200, ['Content-Type' => 'image/webp']),
        ]);
    }

    public function test_imports_post_with_images_categories_and_clean_content(): void
    {
        $this->fakeWordPress([
            $this->wpPost(101, 'pelatihan-dispakati', [2, 3]),
            $this->wpPost(102, 'pelatihan-gis', [2, 4, 3]),
            $this->wpPost(103, 'training-csr', [5], ['_embedded' => ['wp:featuredmedia' => [['code' => 'rest_forbidden']]]]),
        ]);

        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--delay' => 0])->assertSuccessful();

        $post = Post::where('wp_id', 101)->sole();
        $this->assertSame('pelatihan-dispakati', $post->slug);
        $this->assertSame('pelatihan-dispakati', $post->wp_slug);
        $this->assertSame('Pelatihan – Aplikasi Dispakati & SKP', $post->title);
        $this->assertSame('Ringkasan pelatihan Dispakati untuk ASN', $post->excerpt);
        $this->assertSame('2026-09-28 13:15', $post->published_at->format('Y-m-d H:i'));
        $this->assertSame('Bimtek Kepegawaian', $post->category->name);
        $this->assertNotNull($post->user_id);

        // Gambar utama (ukuran large) & gambar isi diunduh ke disk public.
        $this->assertSame('wp/2026/09/10-1024x640.jpg', $post->image);
        Storage::disk('public')->assertExists(['wp/2026/09/10-1024x640.jpg', 'wp/2026/09/foto-kegiatan-1024x768.webp', 'wp/2026/09/modul.pdf']);

        $html = $post->content;
        $this->assertStringNotContainsString('ez-toc', $html);
        $this->assertStringNotContainsString('Table of Contents', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('[caption', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('srcset', $html);
        $this->assertStringNotContainsString('lama.test/wp-content', $html);
        $this->assertStringContainsString('<p><img src="/storage/wp/2026/09/foto-kegiatan-1024x768.webp" alt="Foto &amp; kegiatan"></p>', $html);
        $this->assertStringContainsString('<p><em>Suasana kelas</em></p>', $html);
        $this->assertStringContainsString('href="/artikel-lain/"', $html);
        $this->assertStringContainsString('href="https://dispakati.bkn.go.id/"', $html);
        $this->assertStringContainsString('href="/storage/wp/2026/09/modul.pdf"', $html);
        $this->assertStringContainsString('https://www.youtube.com/watch?v=abcDEF12345', $html);
        $this->assertStringContainsString('<h2>Tujuan Pelatihan</h2>', $html);

        // Kategori paling spesifik dipilih; kategori yang belum ada dibuat.
        $this->assertSame('Bimtek GIS', Post::where('wp_id', 102)->sole()->category->name);
        $this->assertTrue(Category::where('slug', 'bimtek-gis')->exists());
        $this->assertSame('Pelatihan Swasta', Post::where('wp_id', 103)->sole()->category->name);
        $this->assertNull(Post::where('wp_id', 103)->sole()->image);
    }

    public function test_import_is_idempotent_and_update_refreshes(): void
    {
        $this->fakeWordPress([$this->wpPost(101, 'pelatihan-dispakati', [3])]);

        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--delay' => 0])->assertSuccessful();
        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--delay' => 0])->assertSuccessful();
        $this->assertSame(1, Post::where('wp_id', 101)->count());

        Post::where('wp_id', 101)->update(['title' => 'Diubah']);
        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--update' => true])->assertSuccessful();
        $this->assertSame('Pelatihan – Aplikasi Dispakati & SKP', Post::where('wp_id', 101)->value('title'));
        $this->assertSame(1, Post::where('wp_id', 101)->count());
    }

    public function test_dry_run_saves_nothing(): void
    {
        $this->fakeWordPress([$this->wpPost(101, 'pelatihan-dispakati', [4])]);

        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, Post::whereNotNull('wp_id')->count());
        $this->assertFalse(Category::where('slug', 'bimtek-gis')->exists());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_download_does_not_abort_import(): void
    {
        $posts = [$this->wpPost(101, 'satu', [3]), $this->wpPost(102, 'dua', [3])];
        Http::fake([
            self::BASE.'/wp-json/wp/v2/categories*' => Http::response([['id' => 3, 'slug' => 'bimtek-kepegawaian', 'name' => 'Bimtek Kepegawaian', 'count' => 69]]),
            self::BASE.'/wp-json/wp/v2/posts*' => Http::response($posts, 200, ['X-WP-Total' => 2, 'X-WP-TotalPages' => 1]),
            self::BASE.'/wp-content/uploads/2026/09/foto-kegiatan-1024x768.webp' => fn () => throw new ConnectionException('Connection was reset'),
            self::BASE.'/wp-content/uploads/*' => Http::response('binary-image', 200, ['Content-Type' => 'image/webp']),
        ]);

        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--delay' => 0])->assertSuccessful();

        $this->assertSame(2, Post::whereNotNull('wp_id')->count());
        // Gambar gagal tetap memakai URL lama agar masih tampil selama situs lama hidup.
        $this->assertStringContainsString(self::BASE.'/wp-content/uploads/2026/09/foto-kegiatan-1024x768.webp', Post::where('wp_id', 101)->value('content'));
        $this->assertNotNull(Post::where('wp_id', 101)->value('image'));
    }

    public function test_title_h1_in_content_is_removed_and_other_h1_demoted(): void
    {
        $this->fakeWordPress([$this->wpPost(101, 'satu', [3], [
            'content' => ['rendered' => '<h1>Pelatihan – Aplikasi Dispakati &amp; SKP</h1><p>Isi</p><h1>Bagian Lain</h1>'],
            'excerpt' => ['rendered' => '<p>Pelatihan &#8211; Aplikasi Dispakati &amp; SKP era digitalisasi birokrasi [&hellip;]</p>'],
        ])]);

        $this->artisan('wp:import', ['--url' => self::BASE, '--delay' => 0])->assertSuccessful();

        $html = Post::where('wp_id', 101)->value('content');
        $this->assertStringNotContainsString('<h1', $html);
        $this->assertStringNotContainsString('Aplikasi Dispakati', $html);
        $this->assertStringContainsString('<h2>Bagian Lain</h2>', $html);
        // Ringkasan tidak lagi mengulang judul.
        $this->assertSame('Era digitalisasi birokrasi', Post::where('wp_id', 101)->value('excerpt'));
    }

    public function test_limit_option(): void
    {
        $this->fakeWordPress([
            $this->wpPost(101, 'satu', [3]),
            $this->wpPost(102, 'dua', [3]),
            $this->wpPost(103, 'tiga', [3]),
        ]);

        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--limit' => 2])->assertSuccessful();

        $this->assertSame(2, Post::whereNotNull('wp_id')->count());
    }

    public function test_slug_collision_gets_suffix_but_redirect_uses_original(): void
    {
        Post::create(['title' => 'Satu', 'slug' => 'satu', 'published_at' => now()->subDay()]);
        $this->fakeWordPress([$this->wpPost(101, 'satu', [3])]);

        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--delay' => 0])->assertSuccessful();

        $post = Post::where('wp_id', 101)->sole();
        $this->assertSame('satu-2', $post->slug);
        $this->assertSame('satu', $post->wp_slug);
    }

    public function test_old_wordpress_urls_redirect_permanently(): void
    {
        $this->fakeWordPress([$this->wpPost(101, 'pelatihan-dispakati', [3])]);
        $this->artisan('wp:import', ['--delay' => 0, '--url' => self::BASE, '--delay' => 0])->assertSuccessful();

        $this->get('/pelatihan-dispakati/')->assertRedirect('/artikel/pelatihan-dispakati')->assertStatus(301);
        $this->get('/pelatihan-dispakati')->assertStatus(301);
        $this->get('/category/bimtek-kepegawaian/')->assertRedirect('/artikel')->assertStatus(301);
        $this->get('/tag/asn/')->assertStatus(301);
        $this->get('/tidak-ada-artikel-ini/')->assertNotFound();

        // Halaman statis tetap diutamakan.
        $this->get('/tentang-kami')->assertOk();

        $this->get('/artikel/pelatihan-dispakati')->assertOk()
            ->assertSee('Tujuan Pelatihan')
            ->assertSee('/storage/wp/2026/09/foto-kegiatan-1024x768.webp', false);
    }
}
