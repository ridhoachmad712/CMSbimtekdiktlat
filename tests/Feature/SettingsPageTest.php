<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Settings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admins_can_open_settings_page(): void
    {
        foreach ([UserRole::SuperAdmin, UserRole::Admin] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get('/admin/settings')
                ->assertOk()
                ->assertSee('Pengaturan Situs');
        }
    }

    public function test_cs_cannot_access_settings(): void
    {
        $cs = User::factory()->role(UserRole::Cs)->create();

        $this->actingAs($cs)->get('/admin/settings')->assertForbidden();
        $this->actingAs($cs)->get('/admin')->assertOk()->assertDontSee('/admin/settings"', false);
    }

    public function test_form_is_filled_with_current_settings(): void
    {
        $this->actingAs(User::first());

        Livewire::test(Settings::class)
            ->assertSchemaStateSet([
                'site_name' => 'Bimtek Diklat',
                'whatsapp_number' => '6281200000000',
                'social_links.facebook' => '',
            ]);
    }

    public function test_saving_updates_settings_and_clears_cache(): void
    {
        $this->actingAs(User::first());
        $this->assertSame('', Setting::get('email')); // cache terisi

        Livewire::test(Settings::class)
            ->fillForm([
                'site_name' => 'Pusat Bimtek Nasional',
                'whatsapp_number' => '6281299990000',
                'email' => 'admin@bimtek.test',
                'address' => 'Jl. Merdeka No. 1, Jakarta',
                'social_links.instagram' => 'https://instagram.com/bimtek',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Pengaturan disimpan');

        $this->assertSame('Pusat Bimtek Nasional', Setting::get('site_name'));
        $this->assertSame('admin@bimtek.test', Setting::get('email'));
        $this->assertSame('6281299990000', Setting::get('whatsapp_number'));
        $this->assertSame('https://instagram.com/bimtek', Setting::get('social_links')['instagram']);
        $this->assertSame('', Setting::get('social_links')['facebook']);

        // Nilai baru langsung dipakai frontend publik.
        $this->get('/')->assertSee('Pusat Bimtek Nasional');
    }

    public function test_validation_errors(): void
    {
        $this->actingAs(User::first());

        Livewire::test(Settings::class)
            ->fillForm([
                'site_name' => '',
                'whatsapp_number' => 'bukan-nomor',
                'email' => 'bukan email',
                'social_links.youtube' => 'youtube',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'site_name' => 'required',
                'whatsapp_number',
                'email' => 'email',
                'social_links.youtube' => 'url',
            ]);

        $this->assertSame('Bimtek Diklat', Setting::get('site_name'));
    }

    public function test_cs_cannot_save_via_livewire(): void
    {
        $this->actingAs(User::factory()->role(UserRole::Cs)->create());

        Livewire::test(Settings::class)->assertForbidden();
    }

    public function test_search_console_bing_and_ga4_are_output_on_public_pages(): void
    {
        $this->withoutVite();
        $this->actingAs(User::first());

        Livewire::test(Settings::class)
            ->fillForm([
                // Admin menempel tag meta lengkap dari Search Console.
                'google_site_verification' => '<meta name="google-site-verification" content="AbC123_-xyz" />',
                'bing_site_verification' => 'BING99',
                'ga4_measurement_id' => 'G-ABC123XYZ',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('AbC123_-xyz', Setting::get('google_site_verification'));

        $this->get('/')
            ->assertSee('<meta name="google-site-verification" content="AbC123_-xyz">', false)
            ->assertSee('<meta name="msvalidate.01" content="BING99">', false)
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false);

        // Panel admin tidak memuat skrip analitik.
        $this->get('/admin')->assertDontSee('googletagmanager.com', false);
    }

    public function test_seo_fields_are_optional_and_validated(): void
    {
        $this->withoutVite();
        $this->actingAs(User::first());

        $this->get('/')->assertDontSee('google-site-verification', false)->assertDontSee('googletagmanager', false);

        Livewire::test(Settings::class)
            ->fillForm(['ga4_measurement_id' => 'UA-12345'])
            ->call('save')
            ->assertHasFormErrors(['ga4_measurement_id']);
    }
}
