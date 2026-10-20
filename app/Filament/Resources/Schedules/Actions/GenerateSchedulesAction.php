<?php

namespace App\Filament\Resources\Schedules\Actions;

use App\Enums\ScheduleMode;
use App\Models\City;
use App\Models\Schedule;
use App\Models\Topic;
use App\Services\ScheduleGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class GenerateSchedulesAction
{
    public static function make(): Action
    {
        return Action::make('generate')
            ->label('Generator Jadwal')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->modalHeading('Generator Jadwal Berulang')
            ->modalDescription('Buat jadwal untuk beberapa kota sekaligus dengan pola berulang. Jadwal dengan kota dan tanggal mulai yang sama akan dilewati.')
            ->modalSubmitActionLabel('Buat Jadwal')
            ->authorize('create', Schedule::class)
            ->schema([
                Select::make('city_ids')
                    ->label('Kota')
                    ->options(fn () => City::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->required(),
                Grid::make(2)->schema([
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai Pertama')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->default(today()->next('Monday'))
                        ->required(),
                    TextInput::make('interval_weeks')
                        ->label('Interval (minggu)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(52)
                        ->default(1)
                        ->required(),
                    TextInput::make('duration_days')
                        ->label('Durasi (hari)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(30)
                        ->default(4)
                        ->required(),
                    TextInput::make('occurrences')
                        ->label('Jumlah Ulang')
                        ->helperText('Jumlah jadwal per kota.')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(52)
                        ->default(4)
                        ->required(),
                    Select::make('mode')
                        ->label('Mode')
                        ->options(ScheduleMode::class)
                        ->default(ScheduleMode::Offline)
                        ->required(),
                    TextInput::make('quota')
                        ->label('Kuota Peserta')
                        ->numeric()
                        ->minValue(1)
                        ->placeholder('Tanpa batas'),
                ]),
                Select::make('topic_id')
                    ->label('Materi Khusus')
                    ->options(fn () => Topic::query()->orderBy('title')->pluck('title', 'id'))
                    ->searchable()
                    ->placeholder('Jadwal umum (materi dipilih peserta)'),
            ])
            ->action(function (array $data, ScheduleGenerator $generator) {
                $result = $generator->generate(
                    cityIds: $data['city_ids'],
                    startDate: $data['start_date'],
                    intervalWeeks: (int) $data['interval_weeks'],
                    durationDays: (int) $data['duration_days'],
                    occurrences: (int) $data['occurrences'],
                    mode: $data['mode'],
                    topicId: $data['topic_id'] ?? null,
                    quota: filled($data['quota'] ?? null) ? (int) $data['quota'] : null,
                );

                Notification::make()
                    ->title("{$result['created']} jadwal dibuat")
                    ->body($result['skipped'] ? "{$result['skipped']} jadwal dilewati karena sudah ada." : null)
                    ->success()
                    ->send();
            });
    }
}
