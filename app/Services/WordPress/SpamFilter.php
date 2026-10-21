<?php

namespace App\Services\WordPress;

/**
 * Menyaring artikel spam yang disusupkan ke WordPress lama (judi/kasino/obat),
 * agar tidak ikut diimpor. Memakai batas kata supaya "Specialist" tidak terdeteksi "cialis".
 */
class SpamFilter
{
    private const TERMS = [
        '1xbet', 'bet365', 'casino', 'casinos', 'kasino', 'bukmeker', 'bookmaker', 'sportsbook',
        'togel', 'gacor', 'slot online', 'judi online', 'sbobet', 'mostbet', 'melbet', 'pin-up casino',
        'apuestas', 'bono de bienvenida', 'tragamonedas', 'magic 365', 'roulette', 'blackjack',
        'viagra', 'cialis', 'levitra',
    ];

    public static function isSpam(string $title, string $slug = '', string $content = ''): bool
    {
        $text = mb_strtolower($title.' '.str_replace('-', ' ', $slug).' '.strip_tags($content));
        $pattern = '/(?<![\p{L}\p{N}])('.implode('|', array_map(fn ($t) => preg_quote($t, '/'), self::TERMS)).')(?![\p{L}\p{N}])/u';

        // Huruf khas Azerbaijan/Turki pada judul (situs ini berbahasa Indonesia).
        return preg_match($pattern, $text) === 1 || preg_match('/[əğı]/u', mb_strtolower($title)) === 1;
    }
}
