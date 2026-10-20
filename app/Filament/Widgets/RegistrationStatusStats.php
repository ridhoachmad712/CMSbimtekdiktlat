<?php

namespace App\Filament\Widgets;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RegistrationStatusStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Pendaftar per Status';

    protected function getColumns(): int|array|null
    {
        return ['default' => 2, 'md' => 3, 'xl' => count(RegistrationStatus::cases())];
    }

    protected function getStats(): array
    {
        $counts = Registration::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(RegistrationStatus::cases())
            ->map(fn (RegistrationStatus $status) => Stat::make($status->getLabel(), (int) ($counts[$status->value] ?? 0))
                ->color($status->getColor()))
            ->all();
    }
}
