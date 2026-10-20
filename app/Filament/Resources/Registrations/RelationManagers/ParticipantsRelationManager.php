<?php

namespace App\Filament\Resources\Registrations\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $title = 'Daftar Peserta';

    protected static ?string $modelLabel = 'Peserta';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap & Gelar')
                    ->required(),
                TextInput::make('nip')
                    ->label('NIP'),
                TextInput::make('position')
                    ->label('Jabatan'),
                TextInput::make('phone')
                    ->label('No. HP')
                    ->tel(),
                TextInput::make('certificate_number')
                    ->label('No. Sertifikat')
                    ->unique(ignoreRecord: true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->placeholder('-'),
                TextColumn::make('position')
                    ->label('Jabatan')
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->label('No. HP')
                    ->placeholder('-'),
                TextColumn::make('certificate_number')
                    ->label('No. Sertifikat')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
