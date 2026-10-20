<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events tetap aktif agar slug & kode pendaftaran terisi otomatis.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            ['name' => 'Administrator', 'password' => env('ADMIN_PASSWORD', 'password'), 'role' => UserRole::SuperAdmin],
        );

        $this->call([
            CitySeeder::class,
            CategorySeeder::class,
            TopicSeeder::class,
            PackageSeeder::class,
            PageSeeder::class,
            SettingSeeder::class,
            ScheduleSeeder::class,
        ]);

        // Artikel contoh hanya untuk pengembangan; produksi memakai artikel asli (wp:import).
        if (! app()->isProduction()) {
            $this->call(PostSeeder::class);
        }
    }
}
