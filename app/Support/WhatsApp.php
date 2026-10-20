<?php

namespace App\Support;

use App\Models\Setting;

class WhatsApp
{
    /** Nomor WA dari pengaturan, dinormalisasi ke format internasional (62xxx). */
    public static function number(): string
    {
        $number = preg_replace('/\D/', '', (string) Setting::get('whatsapp_number', ''));

        return str_starts_with($number, '0') ? '62'.substr($number, 1) : $number;
    }

    public static function link(?string $message = null): string
    {
        $url = 'https://wa.me/'.static::number();

        return filled($message) ? $url.'?text='.rawurlencode($message) : $url;
    }
}
