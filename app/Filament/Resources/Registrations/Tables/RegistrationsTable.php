<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Filament\Exports\RegistrationExporter;
use App\Filament\Resources\Registrations\Actions\DownloadInvoiceAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label('Tgl Daftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('Nama')
                    ->description(fn ($record) => $record->institution)
                    ->searchable(['contact_name', 'institution']),
                TextColumn::make('phone')
                    ->label('HP/WA')
                    ->searchable(),
                TextColumn::make('schedule.label')
                    ->label('Jadwal'),
                TextColumn::make('topic.title')
                    ->label('Materi')
                    ->limit(35)
                    ->toggleable(),
                TextColumn::make('participants_count')
                    ->label('Peserta')
                    ->numeric(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(RegistrationStatus::class)
                    ->multiple(),
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options(RegistrationSource::class),
                SelectFilter::make('city')
                    ->label('Kota')
                    ->relationship('schedule.city', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WA')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('success')
                    ->url(fn ($record) => 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/\D/', '', $record->phone)))
                    ->openUrlInNewTab(),
                DownloadInvoiceAction::make()
                    ->iconButton(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('changeStatus')
                        ->label('Ubah Status')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->authorize('update')
                        ->schema([
                            Select::make('status')
                                ->options(RegistrationStatus::class)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['status' => $data['status']]);

                            Notification::make()
                                ->title($records->count().' pendaftaran diperbarui')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    ExportBulkAction::make()
                        ->label('Ekspor Terpilih')
                        ->exporter(RegistrationExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
