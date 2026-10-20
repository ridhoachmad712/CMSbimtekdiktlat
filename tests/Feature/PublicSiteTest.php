<?php

namespace Tests\Feature;

use App\Enums\ScheduleMode;
use App\Enums\ScheduleStatus;
use App\Livewire\ScheduleFilter;
use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Models\Post;
use App\Models\Schedule;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_home_page_shows_schedules_categories_and_cities(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Jadwal Bimtek &amp; Diklat ASN '.now()->year.' | Bimtek Diklat</title>', false)
            ->assertSee('Jadwal Bimtek Terdekat')
            ->assertSee('Bimtek Keuangan Daerah')
            ->assertSee('Bimtek Jakarta')
            ->assertSee('https://wa.me/6281200000000', false)
            ->assertSee('"@type":"Event"', false);
    }

    public function test_layout_has_menu_footer_and_meta(): void
    {
        $this->get('/paket')
            ->assertOk()
            ->assertSee('<title>Paket &amp; Biaya Bimtek | Bimtek Diklat</title>', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical" href="'.url('/paket').'">', false)
            ->assertSeeInOrder(['Beranda', 'Jadwal', 'Materi', 'Kota', 'Paket', 'Artikel', 'Daftar'])
            ->assertSee('Hubungi Kami')
            ->assertSee(route('pages.show', 'tentang-kami'), false);
    }

    public function test_schedule_page_renders_filter_and_event_schema(): void
    {
        $this->get('/jadwal')
            ->assertOk()
            ->assertSeeLivewire(ScheduleFilter::class)
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"eventAttendanceMode":"https://schema.org/OfflineEventAttendanceMode"', false);
    }

    public function test_schedule_filter_by_city_month_year_and_mode(): void
    {
        $bali = City::where('slug', 'bali')->first();
        $jakarta = City::where('slug', 'jakarta')->first();
        $date = today()->addMonths(3)->startOfMonth()->addDays(9);

        Schedule::create(['city_id' => $bali->id, 'start_date' => $date, 'end_date' => $date->copy()->addDays(3), 'mode' => ScheduleMode::Online, 'notes' => 'x']);

        Livewire::test(ScheduleFilter::class)
            ->assertSee('Jakarta')
            ->set('city', 'bali')
            ->assertSee('Bali')
            ->assertDontSee(route('cities.show', $jakarta), false)
            ->set('mode', 'online')
            ->assertSee('Online (Zoom)')
            ->set('month', (string) $date->month)
            ->set('year', (string) $date->year)
            ->assertViewHas('schedules', fn ($s) => $s->total() === 1)
            ->call('resetFilters')
            ->assertSet('city', '')
            ->assertViewHas('schedules', fn ($s) => $s->total() > 1);
    }

    public function test_schedule_filter_hides_past_and_closed_schedules(): void
    {
        $city = City::where('slug', 'kupang')->first();
        Schedule::create(['city_id' => $city->id, 'start_date' => today()->subWeek(), 'end_date' => today()->subWeek()->addDays(3)]);
        Schedule::create(['city_id' => $city->id, 'start_date' => today()->addWeek(), 'end_date' => today()->addWeek()->addDays(3), 'status' => ScheduleStatus::Closed]);

        Livewire::test(ScheduleFilter::class)
            ->set('city', 'kupang')
            ->assertViewHas('schedules', fn ($s) => $s->total() === 0)
            ->assertSee('Belum ada jadwal yang sesuai');
    }

    public function test_topic_catalog_pages(): void
    {
        $topic = Topic::with('category')->first();

        $this->get('/materi')->assertOk()->assertSee($topic->category->name)->assertSee($topic->title);

        $this->get("/materi/{$topic->category->slug}")
            ->assertOk()
            ->assertSee($topic->title)
            ->assertSee(route('topics.show', [$topic->category, $topic]), false);

        $this->get("/materi/{$topic->category->slug}/{$topic->slug}")
            ->assertOk()
            // Judul panjang (>60 karakter dengan nama situs) tampil tanpa akhiran nama situs.
            ->assertSee("<title>{$topic->title}</title>", false)
            ->assertSee('Jadwal Pelaksanaan')
            ->assertSee('"@type":"Course"', false)
            ->assertSee('"@type":"Event"', false)
            ->assertSee(route('register', ['jadwal' => Schedule::upcoming()->first()->id, 'materi' => $topic->slug]));
    }

    public function test_topic_must_belong_to_category_and_be_active(): void
    {
        $topic = Topic::first();
        $otherCategory = Category::whereKeyNot($topic->category_id)->first();

        $this->get("/materi/{$otherCategory->slug}/{$topic->slug}")->assertNotFound();
        $this->get('/materi/tidak-ada')->assertNotFound();

        $topic->update(['is_active' => false]);
        $this->get("/materi/{$topic->category->slug}/{$topic->slug}")->assertNotFound();
    }

    public function test_city_pages(): void
    {
        $this->get('/kota')->assertOk()->assertSee('Bimtek Jayapura');

        $this->get('/kota/bali')
            ->assertOk()
            ->assertSee('<title>Bimtek Bali '.now()->year, false)
            ->assertSee('Jadwal di Bali')
            ->assertSee('"addressLocality":"Bali"', false);

        $this->get('/kota/tidak-ada')->assertNotFound();
    }

    public function test_package_page_lists_prices_and_facilities(): void
    {
        $this->get('/paket')
            ->assertOk()
            ->assertSee('Paket Menginap')
            ->assertSee('Rp 5.000.000')
            ->assertSee('Hotel 3 malam (twin share)')
            ->assertSee('minimal 5 peserta');
    }

    public function test_article_pages_show_only_published_posts(): void
    {
        $post = Post::published()->first();
        $draft = Post::create(['title' => 'Artikel Draf', 'content' => '<p>draf</p>']);
        $scheduled = Post::create(['title' => 'Artikel Terjadwal', 'published_at' => now()->addDay()]);

        $this->get('/artikel')->assertOk()->assertSee($post->title)->assertDontSee('Artikel Draf')->assertDontSee('Artikel Terjadwal');
        $this->get("/artikel/{$post->slug}")->assertOk()->assertSee($post->title)->assertSee('"@type":"Article"', false);
        $this->get("/artikel/{$draft->slug}")->assertNotFound();
        $this->get("/artikel/{$scheduled->slug}")->assertNotFound();
    }

    public function test_static_pages(): void
    {
        $this->get('/tentang-kami')->assertOk()->assertSee('Tentang Kami')->assertSee('Bimbingan Teknis');
        $this->get('/legalitas')->assertOk();
        $this->get('/kontak')->assertOk();

        Page::create(['title' => 'Rahasia', 'is_published' => false]);
        $this->get('/rahasia')->assertNotFound();
        $this->get('/tidak-ada')->assertNotFound();
    }

    public function test_catch_all_page_route_does_not_shadow_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_sitemap_lists_public_urls(): void
    {
        $topic = Topic::with('category')->first();

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false)
            ->assertSee('<loc>'.route('schedules').'</loc>', false)
            ->assertSee('<loc>'.route('topics.show', [$topic->category, $topic]).'</loc>', false)
            ->assertSee('<loc>'.url('/kota/bali').'</loc>', false)
            ->assertSee('<loc>'.url('/tentang-kami').'</loc>', false)
            ->assertSee('<loc>'.route('posts.show', Post::published()->first()).'</loc>', false);

        $this->assertNotFalse(simplexml_load_string($response->getContent()));
    }
}
