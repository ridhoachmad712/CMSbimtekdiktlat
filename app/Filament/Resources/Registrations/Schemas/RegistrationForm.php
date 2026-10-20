<?php

namespace App\Filament\Resources\Registrations\Schemas;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Models\Schedule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kegiatan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Pendaftaran')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Dibuat otomatis')
                            ->visibleOn('edit'),
                        Select::make('schedule_id')
                            ->label('Jadwal')
                            ->relationship('schedule', 'start_date', fn (Builder $query) => $query->with('city')->orderBy('start_date'))
                            ->getOptionLabelFromRecordUsing(fn (Schedule $record) => $record->label)
                            ->searchable(['start_date'])
                            ->preload()
                            ->required(),
                        Select::make('topic_id')
                            ->label('Materi')
                            ->relationship('topic', 'title')
                            ->searchable()
                            ->preload(),
                        Select::make('package_id')
                            ->label('Paket')
                            ->relationship('package', 'name')
                            ->preload(),
                        TextInput::make('participants_count')
                            ->label('Jumlah Peserta')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ]),
                Section::make('Kontak / Instansi')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_name')
                            ->label('Nama Lengkap')
                            ->required(),
                        TextInput::make('phone')
                            ->label('No. HP / WhatsApp')
                            ->tel()
                            ->required(),
                        TextInput::make('email')
                            ->email(),
                        TextInput::make('position')
                            ->label('Jabatan'),
                        TextInput::make('institution')
                            ->label('Instansi')
                            ->required(),
                        TextInput::make('region')
                            ->label('Prov / Kab / Kota'),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),
                Section::make('Status')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->options(RegistrationStatus::class)
                            ->default(RegistrationStatus::New)
                            ->required(),
                        Select::make('source')
                            ->label('Sumber')
                            ->options(RegistrationSource::class)
                            ->default(RegistrationSource::Manual)
                            ->required(),
                    ]),
            ]);
    }
}
