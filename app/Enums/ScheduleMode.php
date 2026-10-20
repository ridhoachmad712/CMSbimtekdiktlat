<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ScheduleMode: string implements HasColor, HasLabel
{
    case Offline = 'offline';
    case Online = 'online';
    case Hybrid = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Offline => 'Tatap Muka',
            self::Online => 'Online (Zoom)',
            self::Hybrid => 'Hybrid',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Offline => 'primary',
            self::Online => 'info',
            self::Hybrid => 'warning',
        };
    }
}
