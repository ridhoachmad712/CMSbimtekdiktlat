<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'Jakarta' => 'DKI Jakarta',
            'Bandung' => 'Jawa Barat',
            'Bogor' => 'Jawa Barat',
            'Puncak' => 'Jawa Barat',
            'Semarang' => 'Jawa Tengah',
            'Yogyakarta' => 'DI Yogyakarta',
            'Surabaya' => 'Jawa Timur',
            'Malang' => 'Jawa Timur',
            'Bali' => 'Bali',
            'Lombok' => 'Nusa Tenggara Barat',
            'Kupang' => 'Nusa Tenggara Timur',
            'Banda Aceh' => 'Aceh',
            'Medan' => 'Sumatera Utara',
            'Padang' => 'Sumatera Barat',
            'Pekanbaru' => 'Riau',
            'Batam' => 'Kepulauan Riau',
            'Palembang' => 'Sumatera Selatan',
            'Bengkulu' => 'Bengkulu',
            'Lampung' => 'Lampung',
            'Belitung' => 'Kepulauan Bangka Belitung',
            'Pontianak' => 'Kalimantan Barat',
            'Palangkaraya' => 'Kalimantan Tengah',
            'Banjarmasin' => 'Kalimantan Selatan',
            'Balikpapan' => 'Kalimantan Timur',
            'Samarinda' => 'Kalimantan Timur',
            'IKN (Ibu Kota Nusantara)' => 'Kalimantan Timur',
            'Makassar' => 'Sulawesi Selatan',
            'Manado' => 'Sulawesi Utara',
            'Palu' => 'Sulawesi Tengah',
            'Kendari' => 'Sulawesi Tenggara',
            'Ambon' => 'Maluku',
            'Ternate' => 'Maluku Utara',
            'Sorong' => 'Papua Barat Daya',
            'Manokwari' => 'Papua Barat',
            'Jayapura' => 'Papua',
        ];

        $order = 1;
        foreach ($cities as $name => $province) {
            City::firstOrCreate(['name' => $name], ['province' => $province, 'sort_order' => $order++]);
        }
    }
}
