<?php

namespace App\Services;

use App\Enums\RegistrationSource;
use App\Models\Registration;
use App\Models\Setting;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\NewRegistrationNotification;
use App\Support\WhatsApp;
use Illuminate\Support\Facades\Notification;

class RegistrationAlerts
{
    /** Kirim pemberitahuan ke admin (email dari Setting 'email', WA dari 'whatsapp_number'). */
    public function newRegistration(Registration $registration): void
    {
        if ($registration->source !== RegistrationSource::Web) {
            return;
        }

        $routes = array_filter([
            'mail' => Setting::get('email'),
            WhatsAppChannel::class => WhatsApp::number(),
        ], 'filled');

        if ($routes === []) {
            return;
        }

        Notification::routes($routes)->notify(new NewRegistrationNotification($registration));
    }
}
