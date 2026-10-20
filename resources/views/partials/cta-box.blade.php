<div class="card sticky top-24 p-6">
    <p class="font-semibold text-slate-900">{{ $heading ?? 'Ikuti Bimtek ini' }}</p>
    <p class="mt-1 text-sm text-slate-600">Jadwal setiap minggu di berbagai kota. Daftar online atau konsultasi langsung via WhatsApp.</p>
    <div class="mt-4 flex flex-col gap-2">
        <a href="{{ route('register', $registerParams ?? []) }}" class="btn btn-primary">Daftar Online</a>
        <a href="{{ \App\Support\WhatsApp::link($waMessage ?? 'Halo, saya ingin informasi Bimtek.') }}" target="_blank" rel="noopener" class="btn btn-wa">Tanya via WhatsApp</a>
        <a href="{{ route('schedules') }}" class="btn btn-outline">Lihat Jadwal</a>
    </div>
</div>
