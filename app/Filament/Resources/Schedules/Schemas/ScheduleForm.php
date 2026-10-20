<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Enums\ScheduleMode;
use App\Enums\ScheduleStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Waktu & Tempat')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                // Default durasi 4 hari seperti pola jadwal umum.
                                if ($state && blank($get('end_date'))) {
                                    $set('end_date', now()->parse($state)->addDays(3)->toDateString());
                                }
                            })
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->afterOrEqual('start_date')
                            ->required(),
                        Select::make('city_id')
                            ->label('Kota')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('venue_id', null))
                            ->required(),
                        Select::make('venue_id')
                            ->label('Venue / Hotel')
                            ->relationship('venue', 'name', fn (Builder $query, Get $get) => $query->where('city_id', $get('city_id')))
                            ->searchable()
                            ->preload()
                            ->placeholder('Belum ditentukan'),
                    ]),
                Section::make('Detail')
                    ->columns(2)
                    ->schema([
                        Select::make('topic_id')
                            ->label('Materi Khusus')
                            ->relationship('topic', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('Jadwal umum (materi dipilih peserta)')
                            ->columnSpanFull(),
                        Select::make('mode')
                            ->label('Mode')
                            ->options(ScheduleMode::class)
                            ->default(ScheduleMode::Offline)
                            ->required(),
                        Select::make('status')
                            ->options(ScheduleStatus::class)
                            ->default(ScheduleStatus::Open)
                            ->required(),
                        TextInput::make('quota')
                            ->label('Kuota Peserta')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Tanpa batas'),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
