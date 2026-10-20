<?php

namespace App\Filament\Exports;

use App\Models\Registration;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class RegistrationExporter extends Exporter
{
    protected static ?string $model = Registration::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')->label('Kode'),
            ExportColumn::make('created_at')->label('Tanggal Daftar')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i')),
            ExportColumn::make('status')->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel()),
            ExportColumn::make('source')->label('Sumber')
                ->formatStateUsing(fn ($state) => $state?->getLabel()),
            static::text('contact_name', 'Nama Kontak'),
            static::text('phone', 'HP/WA'),
            static::text('email', 'Email'),
            static::text('institution', 'Instansi'),
            static::text('position', 'Jabatan'),
            static::text('region', 'Daerah'),
            ExportColumn::make('schedule.city.name')->label('Kota'),
            ExportColumn::make('schedule.start_date')->label('Tanggal Mulai')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d')),
            ExportColumn::make('schedule.end_date')->label('Tanggal Selesai')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d')),
            ExportColumn::make('schedule.mode')->label('Mode')
                ->formatStateUsing(fn ($state) => $state?->getLabel()),
            ExportColumn::make('topic.title')->label('Materi'),
            ExportColumn::make('package.name')->label('Paket'),
            ExportColumn::make('package.price')->label('Harga Paket'),
            ExportColumn::make('participants_count')->label('Jumlah Peserta'),
            static::text('notes', 'Catatan')->enabledByDefault(false),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['schedule.city', 'topic', 'package']);
    }

    /**
     * Kolom isian publik: netralkan awalan =, +, -, @ agar tidak dieksekusi
     * sebagai formula oleh aplikasi spreadsheet (CSV injection).
     */
    protected static function text(string $name, string $label): ExportColumn
    {
        return ExportColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state) => is_string($state) && preg_match('/^[=+\-@\t\r]/', $state) ? "'".$state : $state);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor pendaftaran selesai: '.Number::format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
