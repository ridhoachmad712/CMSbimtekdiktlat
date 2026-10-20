<?php

namespace App\Filament\Resources\Venues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VenuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Venue / Hotel')
                    ->searchable(),
                TextColumn::make('city.name')
                    ->label('Kota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('map_url')
                    ->label('Peta')
                    ->formatStateUsing(fn () => 'Buka peta')
                    ->url(fn ($record) => $record->map_url, shouldOpenInNewTab: true)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('city_id')
                    ->label('Kota')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
