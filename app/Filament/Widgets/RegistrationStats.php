<?php

namespace App\Filament\Widgets;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\Schedule;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RegistrationStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Ringkasan Pendaftaran';

    protected function getStats(): array
    {
        $today = Registration::whereDate('created_at', today());
        $month = Registration::where('created_at', '>=', now()->startOfMonth());

        return [
            Stat::make('Pendaftar Hari Ini', (clone $today)->count())
                ->description((clone $today)->sum('participants_count').' peserta')
                ->color('primary'),
            Stat::make('Pendaftar Bulan Ini', (clone $month)->count())
                ->description((clone $month)->sum('participants_count').' peserta'),
            Stat::make('Perlu Dikonfirmasi', Registration::where('status', RegistrationStatus::New)->count())
                ->description('Status: Baru')
                ->color('info'),
            Stat::make('Jadwal 30 Hari ke Depan', Schedule::whereBetween('start_date', [today(), today()->addDays(30)])->count())
                ->color('success'),
        ];
    }
}
