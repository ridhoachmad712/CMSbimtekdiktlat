<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Paket')
                    ->required(),
                TextInput::make('price')
                    ->label('Harga per Peserta')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->default(0),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                TagsInput::make('facilities')
                    ->label('Fasilitas')
                    ->placeholder('Tambah fasilitas')
                    ->columnSpanFull(),
                TextInput::make('min_participants')
                    ->label('Minimal Peserta')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
