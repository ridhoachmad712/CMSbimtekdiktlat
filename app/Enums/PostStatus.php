<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/** Status artikel, diturunkan dari published_at (tidak disimpan sebagai kolom). */
enum PostStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Scheduled => 'Terjadwal',
            self::Published => 'Terbit',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'Belum tampil di situs.',
            self::Scheduled => 'Tampil otomatis pada tanggal terbit.',
            self::Published => 'Sudah tampil di situs.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Published => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Scheduled => Heroicon::OutlinedClock,
            self::Published => Heroicon::OutlinedCheckCircle,
        };
    }
}
