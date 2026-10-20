<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Materi contoh per kategori — lengkapi dari panel admin.
     */
    public function run(): void
    {
        $topics = [
            'Bimtek Keuangan Daerah' => [
                'Penyusunan APBD dan Perubahan APBD Tahun Anggaran Berjalan',
                'Penatausahaan Keuangan Daerah bagi Bendahara Pengeluaran',
                'Penyusunan Laporan Keuangan Pemerintah Daerah Berbasis Akrual',
            ],
            'Bimtek Kepegawaian' => [
                'Manajemen ASN dan Implementasi Sistem Merit',
                'Penyusunan Analisis Jabatan dan Analisis Beban Kerja',
                'Penilaian Kinerja PNS dan Pengelolaan SKP',
            ],
            'Bimtek Aset & Barang Milik Daerah' => [
                'Pengelolaan Barang Milik Daerah sesuai Permendagri 19 Tahun 2016',
                'Inventarisasi dan Penilaian Aset Daerah',
            ],
            'Bimtek Pengadaan Barang/Jasa (PBJ)' => [
                'Pengadaan Barang/Jasa Pemerintah melalui E-Katalog',
                'Persiapan Ujian Sertifikasi PBJ Tingkat Dasar',
            ],
            'Bimtek Desa' => [
                'Pengelolaan Keuangan Desa dan Aplikasi Siskeudes',
                'Penyusunan RPJMDes dan RKPDes',
            ],
            'Bimtek DPRD' => [
                'Peningkatan Kapasitas Pimpinan dan Anggota DPRD',
                'Fungsi Pengawasan DPRD terhadap Pelaksanaan APBD',
            ],
            'Bimtek BLUD' => [
                'Pola Pengelolaan Keuangan BLUD Puskesmas dan RSUD',
            ],
        ];

        foreach ($topics as $categoryName => $titles) {
            $category = Category::where('name', $categoryName)->first();
            if (! $category) {
                continue;
            }

            foreach ($titles as $i => $title) {
                Topic::firstOrCreate(['title' => $title], [
                    'category_id' => $category->id,
                    'summary' => "Bimbingan teknis {$title} bagi aparatur pemerintah daerah, disampaikan oleh narasumber kompeten sesuai regulasi terbaru.",
                    'content' => "<p>Kegiatan ini membekali peserta dengan pemahaman dan keterampilan praktis mengenai {$title}.</p><h2>Pokok bahasan</h2><ul><li>Kebijakan dan regulasi terbaru</li><li>Studi kasus dan praktik terbaik</li><li>Diskusi dan tanya jawab dengan narasumber</li></ul>",
                    'target_participants' => 'Kepala OPD, pejabat struktural/fungsional, dan staf terkait di lingkungan pemerintah daerah.',
                    'is_featured' => $i === 0,
                ]);
            }
        }
    }
}
