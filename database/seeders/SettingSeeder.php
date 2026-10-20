<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name' => 'Bimtek Diklat',
            'site_tagline' => 'Pusat Pendidikan, Pelatihan, Workshop, Bimbingan Teknis, In House Training, dan Seminar',
            'whatsapp_number' => '6281200000000',
            'phone' => '',
            'email' => '',
            'address' => '',
            'social_links' => ['facebook' => '', 'instagram' => '', 'youtube' => ''],
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
