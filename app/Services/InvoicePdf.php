<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Invoice (belum lunas) atau kuitansi (lunas/hadir) PDF per pendaftaran.
 */
class InvoicePdf
{
    public function isReceipt(Registration $registration): bool
    {
        return in_array($registration->status, [RegistrationStatus::Paid, RegistrationStatus::Attended], true);
    }

    public function fileName(Registration $registration): string
    {
        return ($this->isReceipt($registration) ? 'kuitansi-' : 'invoice-').Str::lower($registration->code).'.pdf';
    }

    public function render(Registration $registration): string
    {
        $registration->loadMissing(['schedule.city', 'schedule.venue', 'topic', 'package', 'participants']);

        $unitPrice = (int) ($registration->package?->price ?? 0);
        $quantity = max((int) $registration->participants_count, 1);

        return Pdf::loadView('pdf.invoice', [
            'registration' => $registration,
            'isReceipt' => $this->isReceipt($registration),
            'unitPrice' => $unitPrice,
            'quantity' => $quantity,
            'total' => $unitPrice * $quantity,
            'site' => [
                'name' => Setting::get('site_name', config('app.name')),
                'address' => Setting::get('address'),
                'phone' => Setting::get('phone'),
                'email' => Setting::get('email'),
                'whatsapp' => Setting::get('whatsapp_number'),
            ],
        ])->setPaper('a4')->output();
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
