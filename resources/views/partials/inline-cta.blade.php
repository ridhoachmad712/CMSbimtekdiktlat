{{-- Kotak ajakan di dalam/akhir artikel (terutama untuk ponsel, di mana sidebar ada di bawah). --}}
<aside class="my-6 rounded-xl border border-brand-100 bg-brand-50 p-5" aria-label="Pendaftaran Bimtek">
    <p class="font-semibold text-brand-900">{{ $heading ?? 'Ingin mengikuti pelatihan ini?' }}</p>
    <p class="mt-1 text-sm text-slate-600">Jadwal setiap minggu di berbagai kota. Materi bisa dipilih saat mendaftar.</p>
    <div class="mt-4 grid gap-2 sm:grid-cols-2">
        <a href="{{ route('register', $topic ? ['materi' => $topic->slug] : []) }}" class="btn btn-primary min-h-11">Daftar Online</a>
        <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin informasi jadwal Bimtek.') }}" target="_blank" rel="noopener" class="btn btn-wa min-h-11">Tanya via WhatsApp</a>
    </div>
</aside>
