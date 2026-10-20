<?php

namespace App\Providers\Filament;

use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Identitas dari Pengaturan Situs (nama & logo lembaga).
            ->brandName(fn () => Setting::get('site_name', 'CMS Bimtek Diklat'))
            ->brandLogo(fn () => ($logo = Setting::get('logo')) ? Storage::disk('public')->url($logo) : null)
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            // Logo berwarna gelap dibuat putih otomatis pada mode gelap; tabel berkelas
            // fi-ta-compact (mis. daftar artikel) memakai baris rapat agar lebih banyak terlihat.
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => '<style>'
                .'.dark .fi-logo{filter:brightness(0) invert(1)}'
                .'.fi-ta-compact .fi-ta-row>td{padding-top:0!important;padding-bottom:0!important}'
                .'.fi-ta-compact .fi-ta-row .fi-ta-text{padding-top:.5rem;padding-bottom:.5rem}'
                // Menu atas lebih rapat agar semua grup muat satu baris di layar laptop.
                .'@media (min-width:1024px){.fi-topbar-nav-groups{column-gap:.25rem!important}'
                .'.fi-topbar-nav-groups .fi-topbar-item-btn{padding-inline:.625rem!important}'
                .'.fi-topbar .fi-global-search-field{width:11rem!important}}'
                .'@media (min-width:1024px) and (max-width:1279px){.fi-topbar-nav-groups .fi-topbar-item-icon{display:none}.fi-topbar .fi-global-search-field{width:8rem!important}.fi-topbar-nav-groups .fi-topbar-item-btn{padding-inline:.5rem!important}.fi-topbar-nav-groups>:first-child{display:none}}'
                .'</style>')
            ->colors([
                'primary' => Color::hex('#840b2f'),
            ])
            // Dasbor & Pendaftaran tanpa grup, lalu pekerjaan harian: Konten (artikel).
            ->navigationGroups([
                NavigationGroup::make('Konten'),
                NavigationGroup::make('Jadwal'),
                NavigationGroup::make('Materi'),
                NavigationGroup::make('Pengaturan'),
            ])
            // Menu di atas: grup tampil sebagai dropdown (ponsel/tablet memakai menu geser).
            ->topNavigation()
            // Diperlukan agar tautan unduhan ekspor sampai ke pengguna.
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
