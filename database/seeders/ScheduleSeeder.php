<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Jadwal contoh: 8 minggu ke depan, 4 hari per kegiatan, di beberapa kota utama.
     */
    public function run(): void
    {
        if (Schedule::exists()) {
            return;
        }

        $cities = City::whereIn('name', ['Jakarta', 'Bandung', 'Bali', 'Batam', 'Makassar', 'Yogyakarta', 'Surabaya', 'Lombok'])->get();
        $monday = today()->next('Monday');

        for ($week = 0; $week < 8; $week++) {
            foreach ($cities as $i => $city) {
                $start = $monday->copy()->addWeeks($week)->addDays($i % 3);
                Schedule::create([
                    'city_id' => $city->id,
                    'start_date' => $start,
                    'end_date' => $start->copy()->addDays(3),
                ]);
            }
        }
    }
}
