<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Topic;
use App\Support\ImageVariants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MobileSeoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array<int, array<string, mixed>> semua blok JSON-LD yang valid di halaman */
    private function jsonLd(TestResponse $response): array
    {
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $response->getContent(), $m);

        return array_map(function ($json) {
            $data = json_decode($json, true);
            $this->assertIsArray($data, 'JSON-LD tidak valid: '.mb_substr($json, 0, 120));

            return $data;
        }, $m[1]);
    }

    private function types(array $blocks): array
    {
        return collect($blocks)->flatMap(fn ($b) => isset($b['@graph']) ? $b['@graph'] : (array_is_list($b) ? $b : [$b]))->pluck('@type')->all();
    }

    private function makePost(array $attributes = []): Post
    {
        return Post::create([
            'title' => 'Pelatihan Praktek Aplikasi Dispakati Dalam Penyusunan PAK dan SKP ASN Terbaru 2026',
            'excerpt' => 'Ringkasan artikel.',
            'category_id' => Category::where('slug', 'bimtek-kepegawaian')->value('id'),
            'content' => '<h1>Pelatihan Praktek Aplikasi Dispakati Dalam Penyusunan PAK dan SKP ASN Terbaru 2026</h1>'
                .'<h2>Pengertian</h2><p>Isi.</p><h2>Tujuan</h2><p>Isi.</p><h2>Materi</h2><p>Isi.</p><h2>Jadwal</h2><p>Isi.</p>',
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_article_page_has_valid_schema_single_h1_and_toc(): void
    {
        $post = $this->makePost();
        $response = $this->get("/artikel/{$post->slug}")->assertOk();

        $blocks = $this->jsonLd($response);
        $this->assertContains('Article', $this->types($blocks));
        $this->assertContains('BreadcrumbList', $this->types($blocks));
        $article = collect($blocks)->firstWhere('@type', 'Article');
        $this->assertSame('https://schema.org', $article['@context']);
        $this->assertNotEmpty($article['image']);
        $this->assertSame('Bimtek Kepegawaian', $article['articleSection']);
        $this->assertStringNotContainsString('contextArgs', $response->getContent());

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $response->assertSee('Daftar Isi')
            ->assertSee('<h2 id="pengertian"', false)
            ->assertDontSee('<h2 id="pelatihan-praktek', false)
            ->assertSee('href="#pengertian"', false)
            ->assertSee('Ingin mengikuti pelatihan ini?') // CTA di tengah artikel
            ->assertSee(route('posts.category', $post->category), false);
    }

    public function test_course_and_home_schema_are_valid(): void
    {
        $topic = Topic::first();
        $this->assertContains('Course', $this->types($this->jsonLd($this->get(route('topics.show', [$topic->category, $topic])))));

        $types = $this->types($this->jsonLd($this->get('/')));
        $this->assertContains('EducationalOrganization', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('Event', $types);
    }

    public function test_event_names_are_unique_per_schedule(): void
    {
        $events = collect($this->jsonLd($this->get('/jadwal')))->flatten(1)->where('@type', 'Event');

        $this->assertGreaterThan(1, $events->count());
        $this->assertSame($events->count(), $events->pluck('name')->unique()->count());
    }

    public function test_titles_fit_search_results(): void
    {
        $long = $this->makePost();
        $title = $this->get("/artikel/{$long->slug}")->assertOk()->getContent();
        preg_match('~<title>(.*?)</title>~', $title, $m);
        $this->assertLessThanOrEqual(60, mb_strlen(html_entity_decode($m[1])));

        $short = $this->makePost(['title' => 'Mengenal SKP', 'content' => '<p>x</p>']);
        $this->get("/artikel/{$short->slug}")->assertSee('<title>Mengenal SKP | Bimtek Diklat</title>', false);
    }

    public function test_paginated_pages_have_self_canonical_and_indonesian_pagination(): void
    {
        foreach (range(1, 14) as $i) {
            $this->makePost(['title' => "Artikel {$i}", 'content' => '<p>x</p>', 'published_at' => now()->subDays($i)]);
        }

        $this->get('/artikel')->assertSee('<link rel="canonical" href="'.url('/artikel').'">', false)->assertSee('Berikutnya');
        $this->get('/artikel?page=2')->assertSee('<link rel="canonical" href="'.url('/artikel').'?page=2">', false)->assertSee('Sebelumnya');
    }

    public function test_article_search_and_category_archive(): void
    {
        $this->makePost(['title' => 'Penyusunan SKP Pegawai', 'content' => '<p>x</p>']);
        $this->makePost(['title' => 'Pengelolaan Arsip Dinamis', 'content' => '<p>x</p>', 'category_id' => Category::where('slug', 'bimtek-kearsipan')->value('id')]);

        $this->get('/artikel?q=arsip')->assertOk()
            ->assertSee('Pengelolaan Arsip Dinamis')->assertDontSee('Penyusunan SKP Pegawai')
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get('/artikel/kategori/bimtek-kearsipan')->assertOk()
            ->assertSee('Artikel Bimtek Kearsipan')
            ->assertSee('Pengelolaan Arsip Dinamis')->assertDontSee('Penyusunan SKP Pegawai');

        Category::where('slug', 'bimtek-kearsipan')->update(['is_active' => false]);
        $this->get('/artikel/kategori/bimtek-kearsipan')->assertNotFound();
    }

    public function test_image_variants_and_responsive_markup(): void
    {
        Storage::fake('public');
        Cache::flush();
        $path = UploadedFile::fake()->image('foto.jpg', 1200, 800)->storeAs('wp/2026/09', 'foto.jpg', 'public');

        $this->assertSame(2, ImageVariants::generate($path));
        Storage::disk('public')->assertExists(['wp/2026/09/foto-480w.webp', 'wp/2026/09/foto-960w.webp']);
        $this->assertSame(0, ImageVariants::generate($path)); // tidak dibuat ulang

        $post = $this->makePost(['content' => '<p><img src="/storage/wp/2026/09/foto.jpg" alt="Foto"></p>', 'image' => $path]);
        $this->get("/artikel/{$post->slug}")
            ->assertSee('loading="lazy"', false)
            ->assertSee('/storage/wp/2026/09/foto-480w.webp 480w', false)
            ->assertSee('width="1200" height="800"', false)
            ->assertSee('fetchpriority="high"', false);
    }

    public function test_mobile_cta_bar_and_header(): void
    {
        $this->get('/')->assertSee('aria-label="Aksi cepat"', false)->assertSee('Cari Jadwal')->assertSee('images/icon.svg', false);
        // Halaman daftar: tanpa bar bawah (form punya tombol kirim sendiri), tombol WA tetap ada.
        $this->get('/daftar')->assertDontSee('aria-label="Aksi cepat"', false)->assertSee('Hubungi kami via WhatsApp');

        $topic = Topic::first();
        $this->get(route('topics.show', [$topic->category, $topic]))
            ->assertSee(route('register', ['materi' => $topic->slug]), false);

        Storage::fake('public');
        Setting::updateOrCreate(['key' => 'logo'], ['value' => 'branding/logo.png']);
        $this->get('/')->assertSee('/storage/branding/logo.png', false);
    }

    public function test_about_menu_group_lists_existing_pages(): void
    {
        $this->get('/')
            ->assertSeeInOrder(['Beranda', 'Jadwal', 'Materi', 'Kota', 'Paket', 'Artikel', 'Tentang Kami'])
            ->assertSee('href="'.route('pages.show', 'tentang-kami').'"', false)
            ->assertSee('Profil')
            ->assertSee(route('pages.show', 'kontak'), false)
            // Galeri belum ada di data uji → tidak ditampilkan (hindari tautan 404).
            ->assertDontSee(route('pages.show', 'galeri-kegiatan'), false);

        Page::create(['title' => 'Galeri Kegiatan', 'slug' => 'galeri-kegiatan', 'is_published' => true]);

        $html = $this->get('/kontak')->assertSee(route('pages.show', 'galeri-kegiatan'), false)->getContent();
        $this->assertMatchesRegularExpression('~aria-current="page"\s*>Kontak</a>~', $html);
    }

    public function test_city_description_is_shown(): void
    {
        $city = City::where('slug', 'bali')->first();
        $city->update(['description' => "Pelatihan di Bali diadakan di hotel kawasan Kuta.\n\nAkses mudah dari bandara."]);

        $this->get('/kota/bali')
            ->assertSee('Bimtek &amp; Diklat di Bali', false)
            ->assertSee('<p>Akses mudah dari bandara.</p>', false)
            ->assertSee('<meta name="description" content="Pelatihan di Bali diadakan di hotel kawasan Kuta.', false);
    }

    public function test_icons_and_manifest_exist(): void
    {
        foreach (['favicon.ico', 'images/icon.svg', 'images/apple-touch-icon.png', 'images/logo.png', 'site.webmanifest'] as $file) {
            $this->assertFileExists(public_path($file));
        }
        $this->assertSame(0, json_last_error(), json_decode(file_get_contents(public_path('site.webmanifest'))) ? '' : 'manifest invalid');
    }
}
