<x-layout :title="'Jadwal Bimtek & Diklat ASN '.now()->year" :description="'Jadwal Bimtek & Diklat '.now()->year.' untuk ASN, pejabat daerah, DPRD dan desa di '.$cities->count().' kota di Indonesia. Pilih materi, kota dan tanggal, lalu daftar online.'">
    <x-slot:head>
        <x-schedule-jsonld :schedules="$schedules" />
        <x-jsonld :data="\App\Support\Seo::organization()" />
    </x-slot:head>

    {{-- Hero: ringkas di ponsel, langsung dengan pencarian jadwal. --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-800 via-brand-900 to-brand-950 text-white">
        <div class="container-page grid gap-10 py-10 sm:py-20 lg:grid-cols-5 lg:items-center">
            <div class="lg:col-span-3">
                <p class="inline-block rounded-full bg-white/10 px-3 py-1 text-xs font-medium tracking-wide text-accent-300 uppercase">Bimtek & Diklat {{ now()->year }}</p>
                <h1 class="mt-4 text-3xl leading-tight font-bold tracking-tight text-balance sm:text-5xl">Bimbingan Teknis & Diklat untuk Aparatur Pemerintah</h1>
                <p class="mt-4 max-w-xl text-base text-brand-100 sm:mt-5 sm:text-lg">Jadwal rutin setiap minggu di {{ $cities->count() }} kota — Anda bebas memilih materi.</p>

                <form action="{{ route('schedules') }}" method="get" class="mt-6 grid grid-cols-2 gap-2 rounded-2xl bg-white p-3 text-slate-800 shadow-lg sm:mt-8 sm:grid-cols-[1fr_1fr_auto]" aria-label="Cari jadwal">
                    <div>
                        <label for="hero-kota" class="sr-only">Kota</label>
                        <select id="hero-kota" name="kota" class="form-input">
                            <option value="">Semua kota</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->slug }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="hero-bulan" class="sr-only">Bulan</label>
                        <select id="hero-bulan" name="bulan" class="form-input">
                            <option value="">Semua bulan</option>
                            @foreach (range(0, 5) as $offset)
                                @php($month = now()->locale('id')->startOfMonth()->addMonths($offset))
                                <option value="{{ $month->month }}">{{ $month->translatedFormat('F Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-accent col-span-2 min-h-12 text-base sm:col-span-1">Cari Jadwal</button>
                </form>
            </div>
            <div class="hidden lg:col-span-2 lg:block">
                <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
                    <p class="text-sm font-semibold text-accent-300">Jadwal terdekat</p>
                    <ul class="mt-3 divide-y divide-white/10">
                        @forelse ($schedules->take(4) as $schedule)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <span>
                                    <span class="block font-semibold">{{ $schedule->city->name }}</span>
                                    <span class="text-sm text-brand-100">{{ $schedule->date_range }}</span>
                                </span>
                                <a href="{{ route('register', ['jadwal' => $schedule->id]) }}" class="text-sm font-semibold text-accent-300 hover:text-accent-400">Daftar →</a>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-brand-100">Jadwal segera diumumkan.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Keunggulan: geser horizontal di ponsel. --}}
    <section class="relative mt-6 sm:-mt-6">
        <div class="container-page flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto pb-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:overflow-visible">
            @foreach ([
                ['Jadwal setiap minggu', 'Pelaksanaan rutin di berbagai kota, tatap muka maupun online.'],
                ['Materi sesuai regulasi', 'Materi mengikuti peraturan terbaru dan disampaikan narasumber kompeten.'],
                ['Sertifikat resmi', 'Peserta mendapatkan sertifikat, modul, dan seminar kit.'],
            ] as [$heading, $text])
                <div class="card w-[78%] shrink-0 snap-start p-4 sm:w-auto sm:p-5">
                    <p class="font-semibold text-slate-900">{{ $heading }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Jadwal --}}
    <section class="container-page mt-10 sm:mt-16">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3 sm:mb-6">
            <div>
                <h2 class="section-title">Jadwal Bimtek Terdekat</h2>
                <p class="mt-1 text-slate-600">Jadwal umum — pilih materi saat mendaftar.</p>
            </div>
            <a href="{{ route('schedules') }}" class="btn btn-outline hidden sm:inline-flex">Semua jadwal</a>
        </div>
        @include('partials.schedule-table', ['schedules' => $schedules->take(5)])
        <a href="{{ route('schedules') }}" class="btn btn-outline mt-4 w-full sm:hidden">Lihat semua jadwal</a>
    </section>

    {{-- Model pelatihan (dari beranda situs lama). --}}
    <section class="container-page mt-10 sm:mt-16" aria-labelledby="model-heading">
        <h2 id="model-heading" class="section-title">Model Pelatihan &amp; Bimtek</h2>
        <p class="mt-1 max-w-3xl text-slate-600">Beragam model pelatihan dan bimtek yang dirancang sesuai kebutuhan instansi pemerintah maupun sektor swasta, mulai dari kelas tatap muka, daring, hingga metode hybrid yang fleksibel dan efektif.</p>
        <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ([
                ['heroicon-o-academic-cap', 'Bimbingan Teknis', 'Bimbingan teknis bagi pemerintah daerah maupun peserta umum dari seluruh Indonesia.'],
                ['heroicon-o-wrench-screwdriver', 'Workshop', 'Workshop interaktif untuk meningkatkan keterampilan praktis bersama para ahli berpengalaman.'],
                ['heroicon-o-presentation-chart-bar', 'Seminar', 'Seminar membahas topik aktual dengan narasumber ahli dan sesi diskusi terbuka.'],
                ['heroicon-o-check-badge', 'Sertifikasi', 'Pelatihan resmi dengan pengakuan kompetensi melalui sertifikat untuk meningkatkan kredibilitas peserta.'],
            ] as [$icon, $heading, $text])
                <div class="card p-4 sm:p-5">
                    <span class="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-700">
                        <x-dynamic-component :component="$icon" class="size-6" aria-hidden="true" />
                    </span>
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $heading }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Kategori --}}
    <section class="container-page mt-10 sm:mt-16">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3 sm:mb-6">
            <h2 class="section-title">Kategori Materi</h2>
            <a href="{{ route('topics.index') }}" class="btn btn-outline">Semua materi</a>
        </div>
        <div class="-mx-4 flex snap-x scroll-px-4 gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-3 sm:overflow-visible sm:px-0 lg:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('topics.category', $category) }}" class="card group flex min-h-14 w-64 shrink-0 snap-start items-center justify-between gap-3 p-4 transition hover:border-brand-500 sm:w-auto">
                    <span class="font-medium text-slate-800 group-hover:text-brand-700">{{ $category->name }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $category->topics_count }} materi</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Kota --}}
    <section class="container-page mt-10 sm:mt-16">
        <h2 class="section-title mb-4 sm:mb-6">Kota Pelaksanaan</h2>
        {{-- Dua baris yang bisa digeser di ponsel. --}}
        <div class="-mx-4 grid auto-cols-max grid-flow-col grid-rows-2 gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex sm:flex-wrap sm:px-0">
            @foreach ($cities as $city)
                <a href="{{ route('cities.show', $city) }}" class="chip">Bimtek {{ $city->name }}</a>
            @endforeach
        </div>
    </section>

    {{-- Paket --}}
    @if ($packages->isNotEmpty())
        <section class="container-page mt-10 sm:mt-16">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3 sm:mb-6">
                <h2 class="section-title">Paket & Biaya</h2>
                <a href="{{ route('packages') }}" class="btn btn-outline">Detail paket</a>
            </div>
            <div class="-mx-4 flex snap-x scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:gap-4 md:overflow-visible md:px-0">
                @foreach ($packages as $package)
                    <div class="card w-[70%] shrink-0 snap-start p-5 md:w-auto md:p-6">
                        <p class="font-semibold text-slate-900">{{ $package->name }}</p>
                        @if ($package->hasPrice())
                            <p class="mt-2 text-2xl font-bold text-brand-700">{{ $package->formatted_price }}</p>
                            <p class="text-xs text-slate-500">per peserta{{ $package->min_participants > 1 ? ' · min. '.$package->min_participants.' peserta' : '' }}</p>
                        @else
                            <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(implode(' · ', (array) $package->facilities), 90) }}</p>
                            <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin penawaran '.$package->name.'.') }}" target="_blank" rel="noopener" class="mt-3 inline-block text-sm font-semibold text-brand-700 hover:underline">Minta penawaran →</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Testimoni alumni: geser horizontal di ponsel. --}}
    @if ($testimonials->isNotEmpty())
        <section class="container-page mt-10 sm:mt-16" aria-labelledby="testimoni-heading">
            <h2 id="testimoni-heading" class="section-title mb-4 sm:mb-6">Testimoni Alumni</h2>
            <div class="-mx-4 flex snap-x scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:gap-4 md:overflow-visible md:px-0">
                @foreach ($testimonials as $testimonial)
                    <figure class="card flex w-[82%] shrink-0 snap-start flex-col p-5 md:w-auto">
                        <svg class="size-7 text-accent-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.6 6C6.5 7.3 4.5 10 4.5 13.4V18h6v-6h-3c0-2 1.1-3.6 3-4.4L9.6 6zm9 0c-3.1 1.3-5.1 4-5.1 7.4V18h6v-6h-3c0-2 1.1-3.6 3-4.4L18.6 6z"/></svg>
                        <blockquote class="mt-3 flex-1 text-slate-700">{{ $testimonial->quote }}</blockquote>
                        <figcaption class="mt-4 border-t border-slate-100 pt-3">
                            <span class="block font-semibold text-slate-900">{{ $testimonial->name }}</span>
                            @if ($testimonial->position)
                                <span class="block text-sm text-slate-500">{{ $testimonial->position }}</span>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Artikel --}}
    @if ($posts->isNotEmpty())
        <section class="container-page mt-10 sm:mt-16">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3 sm:mb-6">
                <h2 class="section-title">Artikel Terbaru</h2>
                <a href="{{ route('posts.index') }}" class="btn btn-outline">Semua artikel</a>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="container-page mt-10 sm:mt-16">
        <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-brand-700 p-8 text-white sm:flex-row sm:items-center">
            <div>
                <p class="text-2xl font-bold">Butuh jadwal atau materi khusus?</p>
                <p class="mt-1 text-brand-100">Peserta minimal 8 orang dapat menentukan waktu dan tempat kegiatan sendiri (In House Training).</p>
            </div>
            <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin konsultasi In House Training / jadwal khusus.') }}" target="_blank" rel="noopener" class="btn btn-accent px-5 py-3">Konsultasi via WhatsApp</a>
        </div>
    </section>
</x-layout>
