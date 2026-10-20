<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\City;
use App\Models\Page;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function adminPages(): array
    {
        return collect([
            '', 'registrations', 'registrations/create', 'schedules', 'schedules/create',
            'cities', 'venues', 'packages', 'categories', 'topics', 'topics/create',
            'posts', 'posts/create', 'pages', 'pages/tentang-kami/edit', 'users', 'users/create', 'settings',
        ])->mapWithKeys(fn ($p) => [$p ?: 'dashboard' => [$p]])->all();
    }

    #[DataProvider('adminPages')]
    public function test_admin_page_renders(string $path): void
    {
        $this->actingAs(User::first())->get("/admin/{$path}")->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_slug_is_generated_and_unique(): void
    {
        $this->assertSame('tentang-kami', Page::where('title', 'Tentang Kami')->value('slug'));

        $duplicate = Page::create(['title' => 'Tentang Kami']);
        $this->assertSame('tentang-kami-2', $duplicate->slug);
    }

    public function test_registration_gets_code_and_default_status(): void
    {
        $registration = Registration::create([
            'schedule_id' => Schedule::first()->id,
            'contact_name' => 'Budi',
            'phone' => '081234567890',
            'institution' => 'Dinas Contoh',
        ]);

        $this->assertMatchesRegularExpression('/^REG-\d{6}-[A-Z0-9]{5}$/', $registration->code);
        $this->assertSame(RegistrationStatus::New, $registration->fresh()->status);
    }

    public function test_schedule_label_is_formatted_in_indonesian(): void
    {
        $schedule = Schedule::create([
            'city_id' => City::where('name', 'Bali')->value('id'),
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-23',
        ]);

        $this->assertSame('20 - 23 Oktober 2026 · Bali', $schedule->label);
    }
}
