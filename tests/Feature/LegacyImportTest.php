<?php

namespace Tests\Feature;

use App\Console\Commands\ImportLegacySite;
use App\Models\City;
use App\Models\Package;
use App\Models\Page;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class LegacyImportTest extends TestCase
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
        $this->travelTo('2026-10-20 09:00');

        Http::fake([
            self::BASE.'/wp-json/wc/store/v1/products*' => Http::response([
                ['slug' => 'denpasar', 'short_description' => '<p>Bali menjadi pilihan strategis untuk Bimtek.</p><p>Fasilitas lengkap &amp; nyaman.</p>', 'images' => [['src' => self::BASE.'/wp-content/uploads/2025/07/Bali.jpg']]],
                ['slug' => 'tidak-dikenal', 'short_description' => '<p>x</p>', 'images' => []],
            ]),
            self::BASE.'/wp-content/uploads/*' => Http::response('binary', 200, ['Content-Type' => 'image/png']),
        ]);
    }

    private function import(array $options = []): void
    {
        $this->artisan('legacy:import', ['--url' => self::BASE, ...$options])->assertSuccessful();
    }

    public function test_full_import(): void
    {
        $withRegistration = Schedule::upcoming()->first();
        Registration::create(['schedule_id' => $withRegistration->id, 'contact_name' => 'A', 'phone' => '0812', 'institution' => 'Dinas']);
        $oldCount = Schedule::count();

        $this->import(['--replace-schedules' => true]);

        // Identitas & kontak.
        $this->assertSame('Pusat Edukasi Indonesia', Setting::get('site_name'));
        $this->assertSame('6282138412796', Setting::get('whatsapp_number'));
        $this->assertSame('bimtekdiklat44@gmail.com', Setting::get('email'));
        $this->assertSame('wp/2025/06/layer-1-scaled.png', Setting::get('logo'));
        $this->assertStringEndsWith('.pdf', Setting::get('schedule_pdf'));

        // Halaman.
        $this->assertStringContainsString('NIB:</strong> 2305250063339', Page::where('slug', 'legalitas')->value('content'));
        $this->assertSame('Hubungi Kami', Page::where('slug', 'kontak')->value('title'));
        $this->assertStringContainsString('Visi', Page::where('slug', 'tentang-kami')->value('content'));
        $this->assertSame(8, substr_count(Page::where('slug', 'galeri-kegiatan')->value('content'), '<img'));

        // Kota: hanya 13 kota PDF yang aktif; deskripsi & gambar dari situs lama.
        $this->assertSame(13, City::where('is_active', true)->count());
        $this->assertFalse(City::where('slug', 'jayapura')->value('is_active'));
        $bali = City::where('slug', 'bali')->first();
        $this->assertSame("Bali menjadi pilihan strategis untuk Bimtek.\n\nFasilitas lengkap & nyaman.", $bali->description);
        $this->assertSame('wp/2025/07/bali.jpg', $bali->image);

        // Hotel & jadwal: 14 hotel; 13 kota × 10 tanggal tersisa (22 Okt – 21 Des 2026), 2 hari.
        $hotelNames = collect(ImportLegacySite::HOTELS)->flatten(1)->pluck(0);
        $this->assertSame(14, Venue::whereIn('name', $hotelNames)->count());
        $pdfSchedules = Schedule::whereKeyNot($withRegistration->id)->get();
        $this->assertSame(130, $pdfSchedules->count());
        $this->assertTrue($pdfSchedules->every(fn ($s) => $s->start_date->diffInDays($s->end_date) == 1));
        $this->assertSame('2026-10-22', $pdfSchedules->min('start_date')->toDateString());
        $this->assertSame('Hotel Zia Kuta Bali', Schedule::where('city_id', $bali->id)->first()->venue->name);
        $this->assertNull(Schedule::whereHas('city', fn ($q) => $q->where('slug', 'jakarta'))->whereKeyNot($withRegistration->id)->first()->venue_id);
        // Jadwal lama yang sudah punya pendaftar tidak dihapus.
        $this->assertTrue(Schedule::whereKey($withRegistration->id)->exists());
        $this->assertGreaterThan(0, $oldCount);

        // Testimoni & paket.
        $this->assertSame(5, Testimonial::active()->count());
        $this->assertTrue(Package::all()->every(fn ($p) => ! $p->hasPrice()));
        $this->assertContains('Pelatihan 2 hari', Package::where('name', 'Paket Menginap')->first()->facilities);
    }

    public function test_import_is_idempotent(): void
    {
        $this->import(['--replace-schedules' => true]);
        $counts = [Schedule::count(), Venue::count(), Testimonial::count(), Page::count()];

        $this->import();

        $this->assertSame($counts, [Schedule::count(), Venue::count(), Testimonial::count(), Page::count()]);
    }

    public function test_only_option(): void
    {
        $this->import(['--only' => ['testimonials']]);

        $this->assertSame(5, Testimonial::count());
        $this->assertNotSame('Pusat Edukasi Indonesia', Setting::get('site_name'));

        $this->artisan('legacy:import', ['--url' => self::BASE, '--only' => ['salah']])->assertFailed();
    }

    public function test_public_pages_after_import(): void
    {
        $this->import(['--replace-schedules' => true]);

        $this->get('/')
            ->assertSee('<title>Jadwal Bimtek &amp; Diklat ASN 2026 | Pusat Edukasi Indonesia</title>', false)
            ->assertSee('Testimoni Alumni')
            ->assertSee('Dedi Haryanto')
            ->assertSee('Minta penawaran')
            ->assertDontSee('Rp 5.000.000')
            ->assertSee('/storage/wp/2025/06/layer-1-scaled.png', false);

        $this->get('/jadwal')->assertSee('3 hari kerja')->assertSee('Unduh jadwal (PDF)');
        $this->get('/paket')->assertSee('Biaya: hubungi kami')->assertDontSee('Rp 4.000.000');
        $this->get('/kota/bali')->assertSee('Bali menjadi pilihan strategis')->assertSee('Hotel Zia Kuta Bali');
        $this->get('/kota/jayapura')->assertNotFound();
        $this->get('/kontak')->assertOk()->assertSee('0821 3841 2796');
        $this->get('/galeri-kegiatan')->assertOk();
    }

    public function test_old_site_urls_redirect(): void
    {
        $this->get('/hubungi-kami/')->assertRedirect('/kontak')->assertStatus(301);
        $this->get('/blog/')->assertRedirect('/artikel')->assertStatus(301);
        $this->get('/product/denpasar/')->assertRedirect(route('cities.show', 'bali'))->assertStatus(301);
        $this->get('/product/jakarta/')->assertRedirect(route('cities.show', 'jakarta'));
        $this->get('/cara-pesan/')->assertRedirect('/daftar');
    }
}
