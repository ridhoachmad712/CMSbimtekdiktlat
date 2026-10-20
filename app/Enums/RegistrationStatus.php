<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RegistrationStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Attended = 'attended';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Confirmed => 'Terkonfirmasi',
            self::Paid => 'Lunas',
            self::Attended => 'Hadir',
            self::Cancelled => 'Batal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Confirmed => 'warning',
            self::Paid => 'success',
            self::Attended => 'primary',
            self::Cancelled => 'danger',
        };
    }
}
