<?php

namespace App\Providers;

use App\Contracts\WhatsAppNotifier;
use App\Services\WhatsApp\LogWhatsAppNotifier;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ganti dengan implementasi gateway WA saat sudah tersedia.
        $this->app->bind(WhatsAppNotifier::class, LogWhatsAppNotifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Canonical, sitemap & og:url selalu memakai APP_URL, bukan header Host dari request.
        if ($this->app->isProduction()) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
