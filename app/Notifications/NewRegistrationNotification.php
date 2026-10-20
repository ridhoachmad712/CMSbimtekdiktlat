<?php

namespace App\Notifications;

use App\Filament\Resources\Registrations\RegistrationResource;
use App\Models\Registration;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Pemberitahuan ke admin saat ada pendaftaran baru dari website. */
class NewRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Registration $registration)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return array_keys(array_filter([
            'mail' => filled($notifiable->routes['mail'] ?? null),
            WhatsAppChannel::class => filled($notifiable->routes[WhatsAppChannel::class] ?? null),
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registration = $this->registration->loadMissing(['schedule.city', 'topic', 'package']);

        return (new MailMessage)
            ->subject('Pendaftaran baru '.$registration->code.' — '.$registration->institution)
            ->greeting('Pendaftaran baru dari website')
            ->line('**Kode:** '.$registration->code)
            ->line('**Nama:** '.$registration->contact_name.($registration->position ? ' ('.$registration->position.')' : ''))
            ->line('**Instansi:** '.$registration->institution.($registration->region ? ', '.$registration->region : ''))
            ->line('**HP/WA:** '.$registration->phone.($registration->email ? ' · '.$registration->email : ''))
            ->line('**Materi:** '.($registration->topic?->title ?? '-'))
            ->line('**Jadwal:** '.$registration->schedule?->label)
            ->line('**Paket:** '.($registration->package?->name ?? '-'))
            ->line('**Jumlah peserta:** '.$registration->participants_count)
            ->action('Buka di Panel Admin', RegistrationResource::getUrl('edit', ['record' => $registration], panel: 'admin'))
            ->salutation('Segera hubungi pendaftar untuk konfirmasi.');
    }

    public function toWhatsApp(object $notifiable): string
    {
        $registration = $this->registration->loadMissing(['schedule.city', 'topic']);

        return implode("\n", [
            'Pendaftaran baru '.$registration->code,
            $registration->contact_name.' — '.$registration->institution,
            'HP: '.$registration->phone,
            'Materi: '.($registration->topic?->title ?? '-'),
            'Jadwal: '.$registration->schedule?->label,
            'Peserta: '.$registration->participants_count,
        ]);
    }
}
