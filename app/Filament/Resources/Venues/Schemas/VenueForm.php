<?php

namespace App\Filament\Resources\Venues\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VenueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('city_id')
                    ->label('Kota')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Venue / Hotel')
                    ->required(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
                TextInput::make('map_url')
                    ->label('Tautan Google Maps')
                    ->url(),
            ]);
    }
}
