<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppNotifier;
use App\Enums\RegistrationSource;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Setting;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\NewRegistrationNotification;
use App\Services\WhatsApp\LogWhatsAppNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function register(RegistrationSource $source): Registration
    {
        return Registration::create([
            'schedule_id' => Schedule::first()->id,
            'contact_name' => 'Siti',
            'phone' => '081298765432',
            'institution' => 'Bappeda',
            'source' => $source,
        ]);
    }

    private function setAdminEmail(string $email): void
    {
        Setting::where('key', 'email')->first()->update(['value' => $email]);
    }

    public function test_admin_is_notified_for_web_registration(): void
    {
        Notification::fake();
        $this->setAdminEmail('admin@bimtek.test');

        $registration = $this->register(RegistrationSource::Web);

        Notification::assertSentOnDemand(NewRegistrationNotification::class, function ($notification, array $channels, AnonymousNotifiable $notifiable) use ($registration) {
            return $notification->registration->is($registration)
                && $notifiable->routes['mail'] === 'admin@bimtek.test'
                && in_array('mail', $channels, true)
                && in_array(WhatsAppChannel::class, $channels, true);
        });
        $this->assertInstanceOf(ShouldQueue::class, new NewRegistrationNotification($registration));
    }

    public function test_no_notification_for_manual_registration(): void
    {
        Notification::fake();
        $this->setAdminEmail('admin@bimtek.test');

        $this->register(RegistrationSource::Manual);

        Notification::assertNothingSent();
    }

    public function test_email_skipped_when_setting_empty(): void
    {
        Notification::fake();
        $this->setAdminEmail('');

        $this->register(RegistrationSource::Web);

        Notification::assertSentOnDemand(NewRegistrationNotification::class, fn ($n, array $channels) => $channels === [WhatsAppChannel::class]);
    }

    public function test_mail_content(): void
    {
        Notification::fake();
        $registration = $this->register(RegistrationSource::Web);

        $mail = (new NewRegistrationNotification($registration))->toMail(new AnonymousNotifiable);

        $this->assertStringContainsString($registration->code, $mail->subject);
        $this->assertStringContainsString('/admin/registrations/'.$registration->id.'/edit', $mail->actionUrl);
    }

    public function test_whatsapp_extension_point_receives_message(): void
    {
        $fake = new class implements WhatsAppNotifier
        {
            public array $sent = [];

            public function send(string $to, string $message): void
            {
                $this->sent[] = compact('to', 'message');
            }
        };
        $this->app->instance(WhatsAppNotifier::class, $fake);
        $this->setAdminEmail('admin@bimtek.test');

        $registration = $this->register(RegistrationSource::Web);

        $this->assertCount(1, $fake->sent);
        $this->assertSame('6281200000000', $fake->sent[0]['to']);
        $this->assertStringContainsString($registration->code, $fake->sent[0]['message']);
    }

    public function test_default_whatsapp_binding_only_logs(): void
    {
        $this->assertInstanceOf(LogWhatsAppNotifier::class, app(WhatsAppNotifier::class));
    }
}
