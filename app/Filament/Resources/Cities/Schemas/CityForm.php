<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kota')
                    ->required(),
                TextInput::make('slug')
                    ->unique(ignoreRecord: true)
                    ->helperText('Kosongkan untuk dibuat otomatis dari judul/nama.'),
                TextInput::make('province')
                    ->label('Provinsi'),
                Textarea::make('description')
                    ->label('Deskripsi Kota')
                    ->rows(5)
                    ->helperText('Teks unik untuk halaman kota (baik untuk SEO): lokasi/hotel langganan, akses transportasi, info bagi peserta. Pisahkan paragraf dengan baris kosong.')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Gambar')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('cities'),
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
