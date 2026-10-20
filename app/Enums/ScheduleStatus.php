<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ScheduleStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Full = 'full';
    case Closed = 'closed';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Dibuka',
            self::Full => 'Penuh',
            self::Closed => 'Ditutup',
            self::Done => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Full => 'warning',
            self::Closed => 'danger',
            self::Done => 'gray',
        };
    }
}
