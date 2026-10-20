<?php

namespace App\Console\Commands;

use App\Enums\ScheduleMode;
use App\Enums\ScheduleStatus;
use App\Models\City;
use App\Models\Package;
use App\Models\Page;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Venue;
use App\Services\WordPress\MediaDownloader;
use App\Services\WordPress\WordPressClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Memindahkan data acuan dari situs lama bimtekdiklat.com (Pusat Edukasi Indonesia / PENA):
 * identitas & kontak, halaman profil/legalitas/kontak/galeri, kota & hotel, jadwal 2026 (dari PDF
 * "Jadwal dan Tempat Pelaksanaan Bimtek Diklat 2026"), testimoni, serta penyesuaian paket.
 * Aman diulang. Artikel diimpor terpisah dengan `wp:import`.
 */
class ImportLegacySite extends Command
{
    protected $signature = 'legacy:import
        {--url=https://bimtekdiklat.com : Situs WordPress lama (untuk gambar & deskripsi kota)}
        {--only=* : Bagian tertentu: identity, pages, cities, venues, schedules, testimonials, packages}
        {--replace-schedules : Hapus jadwal lain yang belum punya pendaftar sebelum membuat jadwal dari PDF}';

    protected $description = 'Impor data acuan situs lama (identitas, halaman, kota, hotel, jadwal 2026, testimoni)';

    private const SECTIONS = ['identity', 'pages', 'cities', 'venues', 'schedules', 'testimonials', 'packages'];

    private const PDF_URL = '/wp-content/uploads/2026/07/JADWAL-DAN-TEMPAT-PELAKSAAN-BIMTEKDIKLAT-2026.pdf';

    /** Kota & hotel dari PDF jadwal 2026 (slug kota situs => hotel). */
    public const HOTELS = [
        'jakarta' => [
            ['Hotel Asyana Kemayoran', 'Jl. Bungur Besar Raya No. 1, Jakarta Pusat'],
            ['H! Hotel Senen', 'Kompleks Pasar Senen, Jl. Pasar Senen No. 3, Jakarta'],
        ],
        'bandung' => [['Hotel Serela Cihampelas', 'Jl. Cihampelas, Bandung']],
        'yogyakarta' => [['Fave Hotel Malioboro', 'Jl. I Dewa Nyoman Oka, Kotabaru, Yogyakarta']],
        'bali' => [['Hotel Zia Kuta Bali', 'Jl. Ciung Wanara No. 17, Br. Tegal, Kuta 80361']],
        'surabaya' => [['La Lisa Hotel Surabaya', 'Jl. Raya Nginden No. 82, Surabaya']],
        'malang' => [['MaxOne Hotel Malang', 'Jl. Jaksa Agung Suprapto No. 75A, Kota Malang']],
        'batam' => [['Hotel Aston Gideon Inn Batam', 'Jl. Komp. Penuin Centre No. 1, Batu Selicin, Batam']],
        'makassar' => [['Fave Hotel Pantai Losari', 'Jl. Daeng Tompo No. 28-36, Makassar']],
        'lombok' => [['Hotel Lombok Plaza', 'Jl. Pejanggik No. 8, Cakranegara, Mataram 83231']],
        'medan' => [['Fave Hotel Medan', 'Jl. S. Parman No. 313A, Petisah Hulu, Medan']],
        'manado' => [['Whizz Prime Hotel Manado', 'Kawasan Megamas, Jl. PT Boulevard, Manado']],
        'banjarmasin' => [['Fave Hotel Banjarmasin', 'Jl. Ahmad Yani No. 35, Sungai Baru, Banjarmasin']],
        'balikpapan' => [['Hotel D\'Prima Balikpapan', 'Jl. MT Haryono No. 78, Damai, Balikpapan']],
    ];

    /** Tanggal mulai jadwal 2026 (kegiatan 2 hari). "24 - 24 Juli" di PDF dibaca 23–24 Juli. */
    public const SCHEDULE_2026 = [
        '01-08', '01-15', '01-22', '01-29', '02-05', '02-12', '02-19', '02-26', '03-05', '03-12',
        '04-02', '04-09', '04-16', '04-23', '05-07', '05-12', '05-21', '05-25', '06-04', '06-11', '06-18', '06-25',
        '07-02', '07-09', '07-16', '07-23', '08-06', '08-13', '08-20', '08-27', '09-03', '09-10', '09-17', '09-24',
        '10-01', '10-08', '10-15', '10-22', '10-29', '11-05', '11-12', '11-19', '11-26', '12-03', '12-10', '12-17', '12-21',
    ];

    /** Produk WooCommerce "Daftar Kota" di situs lama → slug kota situs. */
    private const CITY_PRODUCTS = ['jakarta' => 'jakarta', 'denpasar' => 'bali', 'yogyakarta' => 'yogyakarta', 'bandung' => 'bandung'];

    public const TESTIMONIALS = [
        ['Dedi Haryanto', 'Supervisor Keuangan, PT. Abadi Integra Nusa', 'Materi tentang pengelolaan keuangan dan audit internal sangat tepat guna. Setelah mengikuti bimtek, saya merasa lebih percaya diri dalam menjalankan tanggung jawab saya.'],
        ['Andi Prasetyo', 'Staf Perencanaan, Pemkab Sleman', 'Bimtek ini sangat membantu saya memahami perencanaan anggaran daerah dengan lebih sistematis dan aplikatif. Materinya jelas dan narasumbernya kompeten.'],
        ['Siti Rahmawati', 'HRD Manager, PT Mitra Karya', 'Program pelatihan ini memberikan wawasan baru dalam manajemen SDM. Format hybrid-nya juga memudahkan kami mengikuti pelatihan tanpa harus meninggalkan pekerjaan harian.'],
        ['Budi Santoso', 'Kepala Subbag Umum, Pemkot Surabaya', 'Diklat yang saya ikuti sangat relevan dengan tugas saya di pemerintahan. Studi kasusnya nyata dan langsung bisa diterapkan di tempat kerja.'],
        ['Andi Rahmatullah', 'Analis Kebijakan, Pemprov Jabar', 'Saya sangat terkesan dengan pendekatan interaktif yang digunakan. Pelatihan ini tidak hanya teoritis, tapi juga praktis dan membuka wawasan baru.'],
    ];

    private const GALLERY = [
        '2025/11/5d442746-7b9d-4887-b332-3e5f34216359-1024x576.jpeg',
        '2025/11/1d7b175a-a844-45c3-8761-2f2e1ba854a1-1024x768.jpeg',
        '2025/11/14edf14f-218b-4799-a508-ce8fa470c4e5-1024x576.jpeg',
        '2025/11/ea842dcf-348c-47e0-84ac-74385cf1ea56-1024x576.jpeg',
        '2025/11/9f117240-d34a-4bc3-878e-282f5bf43a8f-1024x576.jpeg',
        '2025/11/6086d320-958f-456c-b0ab-55ff07f9f0aa-1024x770.jpeg',
        '2025/11/b2d8cd93-8afc-4e7b-aa23-093c099f6c3a-1024x576.jpeg',
        '2025/11/e6cf3bf6-7378-4cb1-a1b6-0bb1176e13a2-1024x576.jpeg',
    ];

    private WordPressClient $client;

    private MediaDownloader $media;

    public function handle(): int
    {
        $this->client = new WordPressClient($this->option('url'), 150);
        $this->media = new MediaDownloader($this->client);

        $sections = $this->option('only') ?: self::SECTIONS;
        foreach (array_diff($sections, self::SECTIONS) as $unknown) {
            $this->error("Bagian tidak dikenal: {$unknown}");

            return self::INVALID;
        }

        foreach (self::SECTIONS as $section) {
            if (in_array($section, $sections, true)) {
                $this->components->task(Str::headline($section), fn () => $this->{$section}() ?? true);
            }
        }

        if ($this->media->failed) {
            $this->warn('Gagal unduh: '.implode(', ', $this->media->failed));
        }

        return self::SUCCESS;
    }

    private function identity(): bool
    {
        $values = [
            'site_name' => 'Pusat Edukasi Indonesia',
            'site_tagline' => 'Media Pendidikan dan Pengembangan Kompetensi SDM Pemerintah Daerah & Swasta',
            'phone' => '0821 3841 2796',
            'whatsapp_number' => '6282138412796',
            'email' => 'bimtekdiklat44@gmail.com',
            'address' => '18 Office Park Building Lantai 21 Unit C, Jl. TB Simatupang Kav. 18, Kebagusan, Pasar Minggu, Jakarta Selatan, DKI Jakarta 12520',
        ];

        if ($logo = $this->download('/wp-content/uploads/2025/06/Layer-1-scaled.png')) {
            $values['logo'] = $logo;
        }
        if ($pdf = $this->download(self::PDF_URL)) {
            $values['schedule_pdf'] = $pdf;
        }

        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return true;
    }

    private function pages(): bool
    {
        $mitra = ['Kementerian Dalam Negeri', 'Kementerian Keuangan', 'BPK RI', 'Badan Kepegawaian Negara (BKN)', 'Kementerian PAN-RB', 'LKPP', 'BPKP', 'Kementerian Desa PDTT'];

        $pages = [
            'tentang-kami' => ['Tentang Kami', <<<'HTML'
                <p><strong>Pusat Edukasi Indonesia (PENA)</strong> — bersinergi membangun bangsa dan negara dengan pengembangan mutu sumber daya manusia secara profesional.</p>
                <p><strong>Pusat Edukasi Indonesia</strong> adalah lembaga profesional yang berdedikasi dan memberikan layanan di bidang Bimbingan Teknis (Bimtek), Pendidikan dan Pelatihan (Diklat), Workshop, Seminar, Sertifikasi, Riset, Inhouse Training, serta Pelatihan dan Pendidikan Non Formal. Pusat Edukasi Indonesia bertujuan mendukung peningkatan kapasitas, kompetensi, dan keterampilan melalui program yang dirancang secara profesional dan sesuai dengan kebutuhan para peserta.</p>
                <p><strong>Pusat Edukasi Indonesia</strong> memberikan dan menyiapkan dukungan tenaga pengajar atau instruktur yang berasal dari para tenaga ahli dan praktisi yang berpengalaman di bidangnya serta telah tersertifikasi BNSP, serta dukungan dan kerja sama dengan beberapa perguruan tinggi di Indonesia.</p>
                <h2>Visi</h2>
                <p>Ikut bersinergi membangun bangsa dan negara dengan pengembangan mutu sumber daya manusia secara profesional.</p>
                <h2>Misi</h2>
                <ul>
                <li>Meningkatkan kompetensi individu dan organisasi melalui pendekatan pelatihan yang interaktif dan solutif.</li>
                <li>Bekerja sama dengan instansi pemerintah dan swasta untuk mendukung pengembangan sumber daya manusia di Indonesia.</li>
                <li>Meningkatkan kinerja instansi pemerintah dan swasta melalui bimbingan teknis dan pelatihan/training.</li>
                <li>Menyediakan jasa konsultasi manajemen SDM dan tata kelola organisasi/lembaga.</li>
                <li>Menyediakan materi pendidikan &amp; pelatihan berkualitas dan mudah untuk diaplikasikan.</li>
                <li>Menyelenggarakan kerja sama melalui pengembangan jejaring dan kemitraan dengan instansi/lembaga.</li>
                </ul>
                <h2>Kenapa Memilih Kami</h2>
                <h3>Terpercaya dan berpengalaman di bidangnya</h3>
                <p>Kami telah dipercaya dalam menyelenggarakan Bimtek dan pelatihan nasional secara profesional.</p>
                <h3>Narasumber kompeten dan tersertifikasi</h3>
                <p>Didukung oleh tenaga ahli, praktisi berpengalaman, dan instruktur bersertifikat nasional yang ahli di bidangnya.</p>
                <h3>Fasilitas pelatihan nyaman dan lengkap</h3>
                <p>Kami menyediakan sarana belajar yang representatif, modern, dan mendukung proses pelatihan yang efektif.</p>
                <h3>Pusat pelatihan dan bimbingan profesional</h3>
                <p>Bekerja sama dengan kampus terbaik untuk mutu pelatihan maksimal. Instruktur ahli tersertifikasi BNSP.</p>
                HTML
                ."\n<h2>Narasumber &amp; Pemateri</h2>\n<p>Narasumber dan pemateri Bimtek berasal dari instansi berikut:</p>\n<ul>\n"
                .collect($mitra)->map(fn ($m) => "<li>{$m}</li>")->implode("\n")
                ."\n</ul>\n<p>Lihat juga: <a href=\"/legalitas\">Legalitas lembaga</a> · <a href=\"/kontak\">Hubungi kami</a>.</p>",
                'Profil Pusat Edukasi Indonesia (PENA): lembaga penyelenggara Bimtek, Diklat, workshop, seminar, dan sertifikasi bagi instansi pemerintah dan swasta.'],
            'legalitas' => ['Legalitas', <<<'HTML'
                <p>Pusat Edukasi Indonesia (PENA) merupakan lembaga resmi yang terdaftar dengan data legalitas berikut:</p>
                <ul>
                <li><strong>NPWP (Ditjen Pajak):</strong> 1000-0000-0244-9111</li>
                <li><strong>NIB:</strong> 2305250063339</li>
                <li><strong>KBLI:</strong> 82302, 85500, 82301, 70209, 73100</li>
                </ul>
                <p>Salinan dokumen legalitas dapat diminta untuk keperluan administrasi instansi melalui <a href="/kontak">halaman kontak</a>.</p>
                HTML, 'Legalitas Pusat Edukasi Indonesia (PENA): NPWP, NIB, dan KBLI lembaga penyelenggara Bimtek & Diklat.'],
            'kontak' => ['Hubungi Kami', <<<'HTML'
                <p>Tim kami siap membantu Anda. Silakan hubungi kami untuk informasi jadwal, materi, dan pendaftaran Bimtek.</p>
                <h2>Alamat Kantor</h2>
                <p><strong>18 Office Park Building, Lantai 21 Unit C</strong><br>Jl. TB Simatupang Kav. 18, Kebagusan, Pasar Minggu, Jakarta Selatan, DKI Jakarta 12520</p>
                <p><a href="https://maps.app.goo.gl/R264kBE2GgFYZzDK7">Buka di Google Maps</a></p>
                <h2>Kontak</h2>
                <ul>
                <li><strong>Panitia (Lady) HP/WhatsApp:</strong> <a href="https://wa.me/6282138412796">0821 3841 2796</a></li>
                <li><strong>Email:</strong> <a href="mailto:bimtekdiklat44@gmail.com">bimtekdiklat44@gmail.com</a></li>
                </ul>
                <h2>Informasi Pendaftaran</h2>
                <ul>
                <li>Peserta minimal 8 orang dapat menentukan dan menyesuaikan waktu dan tempat kegiatan.</li>
                <li>Pendaftaran selambat-lambatnya 3 hari kerja sebelum kegiatan.</li>
                </ul>
                <p><a href="/daftar">Daftar online sekarang</a></p>
                HTML, 'Hubungi Pusat Edukasi Indonesia (PENA): alamat kantor Jakarta Selatan, WhatsApp 0821 3841 2796, dan email untuk informasi Bimtek.'],
        ];

        $gallery = collect(self::GALLERY)
            ->map(fn ($path) => $this->download('/wp-content/uploads/'.$path))
            ->filter()
            ->map(fn ($path, $i) => '<p><img src="'.MediaDownloader::publicPath($path).'" alt="Dokumentasi kegiatan Bimtek Pusat Edukasi Indonesia '.($i + 1).'"></p>')
            ->implode("\n");
        $pages['galeri-kegiatan'] = ['Galeri Kegiatan', "<p>Dokumentasi kegiatan Bimtek dan pelatihan yang diselenggarakan Pusat Edukasi Indonesia.</p>\n".$gallery,
            'Galeri dokumentasi kegiatan Bimtek dan pelatihan Pusat Edukasi Indonesia (PENA).'];

        foreach ($pages as $slug => [$title, $content, $description]) {
            $page = Page::firstOrNew(['slug' => $slug]);
            $page->fill([
                'title' => $title,
                'content' => preg_replace('/^\s+/m', '', $content),
                'is_published' => true,
                'meta_description' => $description,
            ])->save();
        }

        return true;
    }

    private function cities(): bool
    {
        $active = array_keys(self::HOTELS);
        City::query()->update(['is_active' => false]);
        City::whereIn('slug', $active)->update(['is_active' => true]);
        foreach ($active as $i => $slug) {
            City::where('slug', $slug)->update(['sort_order' => $i + 1]);
        }

        try {
            $products = collect($this->client->download($this->client->baseUrl().'/wp-json/wc/store/v1/products?per_page=50')->json() ?? []);
        } catch (Throwable) {
            $products = collect();
        }

        foreach ($products as $product) {
            $slug = self::CITY_PRODUCTS[$product['slug'] ?? ''] ?? null;
            if (! $slug || ! ($city = City::where('slug', $slug)->first())) {
                continue;
            }

            $text = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br />'], "\n\n", $product['short_description'] ?? '')), ENT_QUOTES | ENT_HTML5);
            $paragraphs = collect(preg_split('/\n{2,}/', $text))->map(fn ($p) => Str::squish($p))->filter()->implode("\n\n");

            $image = ($src = $product['images'][0]['src'] ?? null) && $this->media->isSourceUpload($src) ? $this->media->fetch($src) : null;

            $city->update(array_filter([
                'description' => $paragraphs ?: null,
                'image' => $image,
            ]));
        }

        return true;
    }

    private function venues(): bool
    {
        foreach (self::HOTELS as $slug => $hotels) {
            $city = City::where('slug', $slug)->firstOrFail();
            foreach ($hotels as [$name, $address]) {
                Venue::updateOrCreate(
                    ['city_id' => $city->id, 'name' => $name],
                    ['address' => $address, 'map_url' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($name.', '.$address)],
                );
            }
        }

        return true;
    }

    private function schedules(): bool
    {
        $today = CarbonImmutable::today();
        $dates = collect(self::SCHEDULE_2026)
            ->map(fn ($md) => CarbonImmutable::parse('2026-'.$md))
            ->filter(fn ($date) => $date->gte($today));

        DB::transaction(function () use ($dates) {
            if ($this->option('replace-schedules')) {
                // Hanya jadwal tanpa pendaftar yang dihapus.
                Schedule::whereDoesntHave('registrations')->delete();
            }

            foreach (self::HOTELS as $slug => $hotels) {
                $city = City::where('slug', $slug)->firstOrFail();
                // Kota dengan satu hotel: venue langsung diisi; lebih dari satu: ditentukan saat konfirmasi.
                $venueId = count($hotels) === 1 ? Venue::where('city_id', $city->id)->where('name', $hotels[0][0])->value('id') : null;

                foreach ($dates as $date) {
                    $exists = Schedule::where('city_id', $city->id)->whereDate('start_date', $date->toDateString())->exists();
                    if ($exists) {
                        continue;
                    }
                    Schedule::create([
                        'city_id' => $city->id,
                        'venue_id' => $venueId,
                        'start_date' => $date->toDateString(),
                        'end_date' => $date->addDay()->toDateString(),
                        'mode' => ScheduleMode::Offline,
                        'status' => ScheduleStatus::Open,
                    ]);
                }
            }
        });

        return true;
    }

    private function testimonials(): bool
    {
        foreach (self::TESTIMONIALS as $i => [$name, $position, $quote]) {
            Testimonial::updateOrCreate(['name' => $name], ['position' => $position, 'quote' => $quote, 'sort_order' => $i + 1, 'is_active' => true]);
        }

        return true;
    }

    /** Situs lama tidak mencantumkan harga: harga disembunyikan (0) dan durasi disesuaikan jadwal 2 hari. */
    private function packages(): bool
    {
        foreach (Package::all() as $package) {
            $package->update([
                'price' => 0,
                'facilities' => collect($package->facilities)->map(fn ($f) => match (true) {
                    Str::startsWith($f, 'Pelatihan ') && Str::contains($f, 'hari') => 'Pelatihan 2 hari',
                    Str::startsWith($f, 'Hotel ') => 'Hotel (twin share)',
                    default => $f,
                })->values()->all(),
            ]);
        }

        return true;
    }

    private function download(string $path): ?string
    {
        $url = $this->client->baseUrl().$path;

        return $this->media->isSourceUpload($url) ? $this->media->fetch($url) : null;
    }
}
