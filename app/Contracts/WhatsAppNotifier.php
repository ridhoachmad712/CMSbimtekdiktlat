<?php

namespace App\Contracts;

/**
 * Titik ekstensi pengiriman WhatsApp otomatis. Ganti binding di
 * AppServiceProvider dengan implementasi gateway (Fonnte, Wablas, WA Cloud API, dll).
 */
interface WhatsAppNotifier
{
    /** @param  string  $to  Nomor tujuan format internasional, mis. 6281234567890. */
    public function send(string $to, string $message): void;
}
