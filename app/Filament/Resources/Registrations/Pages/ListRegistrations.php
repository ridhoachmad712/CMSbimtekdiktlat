<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Exports\RegistrationExporter;
use App\Filament\Resources\Registrations\RegistrationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mengikuti filter & pencarian tabel yang sedang aktif.
            ExportAction::make()
                ->label('Ekspor')
                ->exporter(RegistrationExporter::class),
            CreateAction::make(),
        ];
    }
}
