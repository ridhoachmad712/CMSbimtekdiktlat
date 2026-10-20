<?php

namespace App\Services\WordPress;

use App\Models\Category;
use Illuminate\Support\Str;

/**
 * Memetakan kategori WordPress ke kategori situs. Kategori umum dilewati,
 * kategori yang belum ada dibuat. Tiap artikel memakai kategori paling spesifik
 * (paling sedikit artikelnya di WordPress).
 */
class CategoryMapper
{
    /** Slug WordPress → slug kategori yang sudah ada di situs. */
    public const MAP = [
        'bimtek-keuangan' => 'bimtek-keuangan-daerah',
        'bimtek-kepegawaian' => 'bimtek-kepegawaian',
        'bimtek-aset-bmd' => 'bimtek-aset-barang-milik-daerah',
        'bimtek-pengadaan-barang-jasa' => 'bimtek-pengadaan-barangjasa-pbj',
        'bimtek-perpajakan' => 'bimtek-perpajakan',
        'bimtek-pemerintah-daerah' => 'bimtek-pemerintahan-permendagri',
        'bimtek-desa-distrik' => 'bimtek-desa',
        'bimtek-dprd' => 'bimtek-dprd',
        'bimtek-blu-blud' => 'bimtek-blud',
        'bimtek-kearsipan' => 'bimtek-kearsipan',
        'bimtek-lingkungan-hidup' => 'bimtek-lingkungan-hidup',
        'bimtek-rumah-sakit-puskesmas' => 'bimtek-rumah-sakit-kesehatan',
        'bimtek-kesehatan' => 'bimtek-rumah-sakit-kesehatan',
        'bimtek-pertanahan' => 'bimtek-pertanahan',
        // Dua kategori WordPress untuk pelatihan swasta digabung.
        'training-swasta' => 'pelatihan-swasta',
    ];

    /** Kategori umum/promosi yang tidak menjadi kategori artikel. */
    public const GENERIC = [
        'uncategorized', 'materi-bimtek-pilihan', 'pelatihan-umum', 'pelatihan-terbaru', 'training-terbaru',
    ];

    /** @var array<string, int> cache slug situs → id */
    private array $resolved = [];

    /** @var array<int, string> */
    public array $created = [];

    /** @param  array<int, array<string, mixed>>  $wpCategories  keyed by WP id */
    public function __construct(private array $wpCategories, private bool $dryRun = false) {}

    /**
     * @param  array<int, int>  $wpCategoryIds
     * @return array{id: int|null, name: string|null}
     */
    public function resolve(array $wpCategoryIds): array
    {
        $candidates = collect($wpCategoryIds)
            ->map(fn ($id) => $this->wpCategories[$id] ?? null)
            ->filter()
            ->reject(fn ($c) => in_array($c['slug'], self::GENERIC, true))
            ->sortBy('count')
            ->values();

        $wp = $candidates->first();
        if (! $wp) {
            return ['id' => null, 'name' => null];
        }

        $slug = self::MAP[$wp['slug']] ?? $wp['slug'];
        $name = $slug === 'pelatihan-swasta' ? 'Pelatihan Swasta' : html_entity_decode($wp['name']);

        return ['id' => $this->categoryId($slug, $name), 'name' => $name];
    }

    private function categoryId(string $slug, string $name): ?int
    {
        if (isset($this->resolved[$slug])) {
            return $this->resolved[$slug];
        }

        $category = Category::where('slug', $slug)->first();
        if (! $category && $this->dryRun) {
            $this->created[$slug] = $name.' (baru)';

            return null;
        }

        if (! $category) {
            $category = Category::create([
                'name' => Str::of($name)->squish()->toString(),
                'slug' => $slug,
                'sort_order' => 100 + count($this->created),
                'is_active' => true,
            ]);
            $this->created[$slug] = $category->name;
        }

        return $this->resolved[$slug] = $category->id;
    }
}
