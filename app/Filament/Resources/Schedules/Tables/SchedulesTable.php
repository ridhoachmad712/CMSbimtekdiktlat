<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Enums\ScheduleMode;
use App\Enums\ScheduleStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_date')
            ->columns([
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('city.name')
                    ->label('Kota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('topic.title')
                    ->label('Materi')
                    ->placeholder('Umum')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('mode')
                    ->badge(),
                TextColumn::make('registrations_count')
                    ->label('Pendaftar')
                    ->counts('registrations')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('city_id')
                    ->label('Kota')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('mode')
                    ->options(ScheduleMode::class),
                SelectFilter::make('status')
                    ->options(ScheduleStatus::class),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn ($q, $date) => $q->whereDate('start_date', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->whereDate('start_date', '<=', $date))),
                Filter::make('upcoming')
                    ->label('Hanya yang akan datang')
                    ->default()
                    ->query(fn (Builder $query) => $query->whereDate('start_date', '>=', today())),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Duplikat'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
