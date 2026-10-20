<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Artikel contoh — ganti dari panel admin.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Pentingnya Bimtek Penyusunan APBD bagi Pengelola Keuangan Daerah',
                'category' => 'Bimtek Keuangan Daerah',
                'excerpt' => 'Penyusunan APBD yang tepat waktu dan sesuai regulasi menjadi kunci tata kelola keuangan daerah yang akuntabel.',
            ],
            [
                'title' => 'Mengenal Sistem Merit dalam Manajemen ASN',
                'category' => 'Bimtek Kepegawaian',
                'excerpt' => 'Sistem merit menempatkan kualifikasi, kompetensi, dan kinerja sebagai dasar kebijakan manajemen ASN.',
            ],
            [
                'title' => 'Tips Memilih Program Bimtek yang Tepat untuk Instansi Anda',
                'category' => null,
                'excerpt' => 'Sesuaikan materi, waktu, dan lokasi Bimtek dengan kebutuhan pengembangan kompetensi pegawai.',
            ],
        ];

        foreach ($posts as $i => $data) {
            Post::firstOrCreate(['title' => $data['title']], [
                'category_id' => $data['category'] ? Category::where('name', $data['category'])->value('id') : null,
                'excerpt' => $data['excerpt'],
                'content' => "<p>{$data['excerpt']}</p><p>Melalui bimbingan teknis, peserta memperoleh pemahaman regulasi terbaru sekaligus kesempatan berdiskusi langsung dengan narasumber yang berpengalaman.</p>",
                'published_at' => now()->subDays(($i + 1) * 3),
            ]);
        }
    }
}
