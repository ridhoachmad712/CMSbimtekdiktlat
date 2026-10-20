<x-layout title="Paket & Biaya Bimtek" description="Paket Bimtek & Diklat: menginap, tidak menginap, dan online (Zoom), lengkap dengan fasilitas peserta. Minta penawaran via WhatsApp.">
    <x-page-header title="Paket & Biaya" subtitle="Pilihan paket kegiatan. Peserta minimal 8 orang dapat menentukan waktu dan tempat kegiatan sendiri." :breadcrumbs="['Paket' => null]" />

    <div class="container-page mt-8 grid gap-6 md:grid-cols-3">
        @forelse ($packages as $package)
            <section @class(['card flex flex-col p-6', 'ring-2 ring-brand-500' => $loop->first])>
                @if ($loop->first)
                    <span class="mb-3 self-start rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">Paling diminati</span>
                @endif
                <h2 class="text-lg font-semibold text-slate-900">{{ $package->name }}</h2>
                @if ($package->hasPrice())
                    <p class="mt-3 text-3xl font-bold text-brand-700">{{ $package->formatted_price }}</p>
                    <p class="text-sm text-slate-500">per peserta{{ $package->min_participants > 1 ? ' · minimal '.$package->min_participants.' peserta' : '' }}</p>
                @else
                    <p class="mt-3 text-lg font-semibold text-brand-700">Biaya: hubungi kami</p>
                    @if ($package->min_participants > 1)
                        <p class="text-sm text-slate-500">minimal {{ $package->min_participants }} peserta</p>
                    @endif
                @endif
                @if ($package->description)
                    <p class="mt-4 text-sm text-slate-600">{{ $package->description }}</p>
                @endif
                @if ($package->facilities)
                    <ul class="mt-5 space-y-2 text-sm text-slate-700">
                        @foreach ($package->facilities as $facility)
                            <li class="flex gap-2">
                                <svg class="mt-0.5 size-4 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0z" clip-rule="evenodd"/></svg>
                                {{ $facility }}
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-6 grid gap-2">
                    <a href="{{ route('register', ['paket' => $package->id]) }}" class="btn btn-primary w-full">Pilih paket ini</a>
                    @unless ($package->hasPrice())
                        <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin penawaran biaya '.$package->name.'.') }}" target="_blank" rel="noopener" class="btn btn-wa w-full">Minta penawaran</a>
                    @endunless
                </div>
            </section>
        @empty
            <div class="card p-8 text-center text-slate-500 md:col-span-3">Informasi biaya dapat ditanyakan via WhatsApp.</div>
        @endforelse
    </div>

    <div class="container-page mt-8">
        <div class="card p-6 text-sm text-slate-600">
            Biaya dapat dibebankan pada anggaran instansi masing-masing. Pendaftaran selambat-lambatnya 3 hari kerja sebelum kegiatan.
            Untuk <em>In House Training</em> atau penawaran grup, silakan
            <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin penawaran In House Training / grup.') }}" class="font-semibold text-brand-700 hover:underline" target="_blank" rel="noopener">hubungi kami via WhatsApp</a>.
        </div>
    </div>
</x-layout>
