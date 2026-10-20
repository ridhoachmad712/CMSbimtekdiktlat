@php
    use App\Services\InvoicePdf;
    $title = $isReceipt ? 'KUITANSI' : 'INVOICE';
    $schedule = $registration->schedule;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} {{ $registration->code }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; }
        h1 { font-size: 22px; margin: 0; letter-spacing: 2px; color: #840b2f; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .meta td { padding: 2px 0; }
        .items { margin-top: 18px; }
        .items th { background: #fdf2f4; color: #6c0a28; text-align: left; padding: 7px 8px; border-bottom: 1px solid #f5c6d1; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .right, .items th.right { text-align: right; }
        .total td { font-weight: bold; font-size: 12px; border-bottom: none; }
        .box { margin-top: 18px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 4px; }
        .stamp { display: inline-block; padding: 4px 12px; border: 2px solid #840b2f; color: #840b2f; font-weight: bold; letter-spacing: 2px; }
        .sign { margin-top: 36px; width: 40%; margin-left: 60%; text-align: center; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td style="width: 60%">
                <strong style="font-size: 14px">{{ $site['name'] }}</strong><br>
                @if ($site['address'])<span class="muted">{{ $site['address'] }}</span><br>@endif
                @if ($site['phone'])<span class="muted">Telp: {{ $site['phone'] }}</span><br>@endif
                @if ($site['whatsapp'])<span class="muted">WA: {{ $site['whatsapp'] }}</span><br>@endif
                @if ($site['email'])<span class="muted">{{ $site['email'] }}</span>@endif
            </td>
            <td class="right">
                <h1>{{ $title }}</h1>
                <table class="meta">
                    <tr><td class="right muted">No.</td><td class="right" style="width: 55%">{{ $registration->code }}</td></tr>
                    <tr><td class="right muted">Tanggal</td><td class="right">{{ now()->locale('id')->translatedFormat('d F Y') }}</td></tr>
                    <tr><td class="right muted">Status</td><td class="right">{{ $registration->status->getLabel() }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="box">
        <span class="muted">{{ $isReceipt ? 'Telah diterima dari' : 'Ditagihkan kepada' }}:</span><br>
        <strong>{{ $registration->contact_name }}</strong>@if ($registration->position) — {{ $registration->position }}@endif<br>
        {{ $registration->institution }}@if ($registration->region), {{ $registration->region }}@endif<br>
        <span class="muted">{{ $registration->phone }}@if ($registration->email) · {{ $registration->email }}@endif</span>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Uraian</th>
                <th class="right" style="width: 18%">Harga</th>
                <th class="right" style="width: 10%">Peserta</th>
                <th class="right" style="width: 20%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $registration->topic?->title ?? 'Bimbingan Teknis' }}</strong><br>
                    <span class="muted">
                        {{ $schedule?->label }}@if ($schedule?->mode) · {{ $schedule->mode->getLabel() }}@endif
                        @if ($schedule?->venue)<br>{{ $schedule->venue->name }}@endif
                        @if ($registration->package)<br>{{ $registration->package->name }}@endif
                    </span>
                </td>
                <td class="right">{{ $unitPrice ? InvoicePdf::rupiah($unitPrice) : '-' }}</td>
                <td class="right">{{ $quantity }}</td>
                <td class="right">{{ $total ? InvoicePdf::rupiah($total) : '-' }}</td>
            </tr>
            <tr class="total">
                <td colspan="3" class="right">Total</td>
                <td class="right">{{ $total ? InvoicePdf::rupiah($total) : 'Menyesuaikan paket' }}</td>
            </tr>
        </tbody>
    </table>

    @if ($registration->participants->isNotEmpty())
        <div class="box">
            <strong>Daftar Peserta</strong>
            <ol style="margin: 6px 0 0 16px; padding: 0">
                @foreach ($registration->participants as $participant)
                    <li>{{ $participant->name }}@if ($participant->nip) (NIP {{ $participant->nip }})@endif</li>
                @endforeach
            </ol>
        </div>
    @endif

    @if ($isReceipt)
        <p style="margin-top: 18px"><span class="stamp">LUNAS</span></p>
    @else
        <p class="muted" style="margin-top: 18px">Mohon lakukan pembayaran sesuai total di atas dan kirimkan bukti transfer melalui WhatsApp dengan menyebutkan nomor {{ $registration->code }}.</p>
    @endif

    <div class="sign">
        {{ $site['name'] }}<br><br><br><br>
        ( ______________________ )
    </div>
</body>
</html>
