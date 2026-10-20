<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Schedule;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingSchedules extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Jadwal Terdekat';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Schedule::query()->upcoming()->with(['city', 'topic'])->withCount('registrations')->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('start_date')
                    ->label('Tanggal')
                    ->formatStateUsing(fn (Schedule $record) => $record->start_date->locale('id')->translatedFormat('d M').' – '.$record->end_date->locale('id')->translatedFormat('d M Y')),
                TextColumn::make('city.name')
                    ->label('Kota'),
                TextColumn::make('topic.title')
                    ->label('Materi')
                    ->placeholder('Umum')
                    ->limit(40),
                TextColumn::make('mode')
                    ->badge(),
                TextColumn::make('registrations_count')
                    ->label('Pendaftar'),
                TextColumn::make('quota')
                    ->label('Kuota')
                    ->placeholder('-'),
            ])
            ->recordUrl(fn (Schedule $record) => ScheduleResource::canEdit($record) ? ScheduleResource::getUrl('edit', ['record' => $record]) : null);
    }
}
