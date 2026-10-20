<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'Tentang Kami' => '<p>Kami bergerak di bidang Pendidikan dan Pelatihan, Workshop, Bimbingan Teknis, In House Training, dan Seminar bagi instansi pemerintah maupun swasta.</p>',
            'Legalitas' => '<p>Isi dengan dokumen legalitas lembaga.</p>',
            'Kontak' => '<p>Isi dengan alamat dan kontak sekretariat.</p>',
        ];

        foreach ($pages as $title => $content) {
            Page::firstOrCreate(['title' => $title], ['content' => $content]);
        }
    }
}
