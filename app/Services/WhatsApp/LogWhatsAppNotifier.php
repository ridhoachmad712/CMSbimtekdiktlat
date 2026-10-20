<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppNotifier;
use Illuminate\Support\Facades\Log;

/** Implementasi bawaan: hanya mencatat pesan ke log, tidak mengirim apa pun. */
class LogWhatsAppNotifier implements WhatsAppNotifier
{
    public function send(string $to, string $message): void
    {
        Log::info('WhatsApp (log driver) ke '.$to, ['message' => $message]);
    }
}
