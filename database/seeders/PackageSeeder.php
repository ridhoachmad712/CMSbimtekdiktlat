<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // Harga contoh — sesuaikan dari panel admin.
        $packages = [
            [
                'name' => 'Paket Menginap',
                'price' => 5_000_000,
                'facilities' => ['Pelatihan 4 hari', 'Hotel 3 malam (twin share)', 'Konsumsi lengkap', 'Seminar kit & modul', 'Sertifikat', 'Tas eksklusif'],
            ],
            [
                'name' => 'Paket Tidak Menginap',
                'price' => 4_000_000,
                'facilities' => ['Pelatihan 4 hari', 'Coffee break & makan siang', 'Seminar kit & modul', 'Sertifikat', 'Tas eksklusif'],
            ],
            [
                'name' => 'Paket Online (Zoom)',
                'price' => 1_500_000,
                'min_participants' => 5,
                'facilities' => ['Sesi daring via Zoom', 'Modul digital', 'E-sertifikat'],
            ],
        ];

        foreach ($packages as $i => $data) {
            Package::firstOrCreate(['name' => $data['name']], $data + ['sort_order' => $i + 1]);
        }
    }
}
