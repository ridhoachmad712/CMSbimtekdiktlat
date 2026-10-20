<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RegistrationSource: string implements HasLabel
{
    case Web = 'web';
    case WhatsApp = 'whatsapp';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Web => 'Website',
            self::WhatsApp => 'WhatsApp',
            self::Manual => 'Input Manual',
        };
    }
}
