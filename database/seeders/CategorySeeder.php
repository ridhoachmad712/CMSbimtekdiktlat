<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Kategori umum di pasar Bimtek/Diklat pemerintahan (lihat docs/analisa-kompetitor.md).
        $categories = [
            'Bimtek Keuangan Daerah',
            'Bimtek Kepegawaian',
            'Bimtek Aset & Barang Milik Daerah',
            'Bimtek Pengadaan Barang/Jasa (PBJ)',
            'Bimtek Perencanaan, Renstra & Renja',
            'Bimtek Perpajakan',
            'Bimtek Pemerintahan & Permendagri',
            'Bimtek Desa',
            'Bimtek DPRD',
            'Bimtek BLUD',
            'Bimtek Kearsipan',
            'Bimtek Lingkungan Hidup',
            'Bimtek ESDM',
            'Bimtek Rumah Sakit & Kesehatan',
            'Bimtek Pertanahan',
        ];

        foreach ($categories as $i => $name) {
            Category::firstOrCreate(['name' => $name], ['sort_order' => $i + 1]);
        }
    }
}
