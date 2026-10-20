{{-- Ketentuan pendaftaran (dari PDF "Jadwal dan Tempat Pelaksanaan Bimtek Diklat"). --}}
@php($pdf = ($showPdf ?? true) ? \App\Models\Setting::get('schedule_pdf') : null)
<div class="mb-5 flex flex-col gap-3 rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm text-slate-700 sm:flex-row sm:items-center sm:justify-between">
    <ul class="space-y-1">
        <li>• Pendaftaran selambat-lambatnya <strong>3 hari kerja</strong> sebelum kegiatan.</li>
        <li>• Peserta minimal <strong>8 orang</strong> dapat menentukan waktu dan tempat kegiatan sendiri.</li>
    </ul>
    @if ($pdf)
        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($pdf) }}" target="_blank" rel="noopener" class="btn btn-outline shrink-0">
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.6L6.3 8.4a.75.75 0 1 0-1.1 1.04l4.25 4.5a.75.75 0 0 0 1.1 0l4.25-4.5a.75.75 0 1 0-1.1-1.04l-2.95 2.95v-8.6z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
            Unduh jadwal (PDF)
        </a>
    @endif
</div>
