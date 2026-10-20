<?php

namespace App\Filament\Resources\Registrations\Actions;

use App\Models\Registration;
use App\Services\InvoicePdf;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DownloadInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('invoice')
            ->label(fn (Registration $record) => app(InvoicePdf::class)->isReceipt($record) ? 'Kuitansi' : 'Invoice')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->authorize('view')
            ->action(function (Registration $record, InvoicePdf $invoice) {
                $pdf = $invoice->render($record);

                return response()->streamDownload(fn () => print ($pdf), $invoice->fileName($record), [
                    'Content-Type' => 'application/pdf',
                ]);
            });
    }
}
