@php
    $menu = [
        ['label' => 'Beranda', 'route' => 'home', 'active' => 'home'],
        ['label' => 'Jadwal', 'route' => 'schedules', 'active' => 'schedules'],
        ['label' => 'Materi', 'route' => 'topics.index', 'active' => 'topics.*'],
        ['label' => 'Kota', 'route' => 'cities.index', 'active' => 'cities.*'],
        ['label' => 'Paket', 'route' => 'packages', 'active' => 'packages'],
        ['label' => 'Artikel', 'route' => 'posts.index', 'active' => 'posts.*'],
    ];

    // Grup "Tentang Kami" (seperti menu situs lama); hanya halaman yang ada & terbit.
    $aboutLabels = ['tentang-kami' => 'Profil', 'galeri-kegiatan' => 'Galeri Kegiatan', 'legalitas' => 'Legalitas', 'kontak' => 'Kontak'];
    $published = \App\Models\Page::where('is_published', true)->whereIn('slug', array_keys($aboutLabels))->pluck('slug')->all();
    $about = collect($aboutLabels)->only($published);
    $currentPage = request()->routeIs('pages.show') ? request()->route('page') : null;
    $aboutActive = $about->has($currentPage);

    $logo = \App\Models\Setting::get('logo');
    $phone = \App\Models\Setting::get('phone');
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="container-page flex h-16 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex min-h-11 shrink-0 items-center gap-2 font-bold text-brand-800" aria-label="{{ $siteName }} — Beranda">
            @if ($logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="{{ $siteName }}" class="h-9 w-auto" height="36">
            @else
                <img src="{{ asset('images/icon.svg') }}" alt="" class="size-9" width="36" height="36">
                <span class="text-lg leading-tight">{{ $siteName }}</span>
            @endif
        </a>

        <nav class="hidden items-center gap-0.5 md:flex lg:gap-1" aria-label="Menu utama">
            @foreach ($menu as $item)
                <a href="{{ route($item['route']) }}"
                   @class([
                       'rounded-lg px-2 py-2 text-sm font-medium whitespace-nowrap transition lg:px-3',
                       'bg-brand-50 text-brand-700' => request()->routeIs($item['active']),
                       'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($item['active']),
                   ])
                   @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                    {{ $item['label'] }}
                </a>
            @endforeach

            @if ($about->isNotEmpty())
                {{-- Dropdown tanpa framework JS: <details>, ditutup otomatis oleh resources/js/app.js. --}}
                <details class="group/about relative" data-dropdown>
                    <summary @class([
                        'flex cursor-pointer list-none items-center gap-1 rounded-lg px-2 py-2 text-sm font-medium whitespace-nowrap transition lg:px-3 [&::-webkit-details-marker]:hidden',
                        'bg-brand-50 text-brand-700' => $aboutActive,
                        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $aboutActive,
                    ])>
                        Tentang Kami
                        <svg class="size-4 transition group-open/about:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4z" clip-rule="evenodd"/></svg>
                    </summary>
                    <div class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                        @foreach ($about as $slug => $label)
                            <a href="{{ route('pages.show', $slug) }}"
                               @class(['block rounded-lg px-3 py-2.5 text-sm font-medium', 'bg-brand-50 text-brand-700' => $currentPage === $slug, 'text-slate-700 hover:bg-slate-100' => $currentPage !== $slug])
                               @if ($currentPage === $slug) aria-current="page" @endif>{{ $label }}</a>
                        @endforeach
                    </div>
                </details>
            @endif

            <a href="{{ route('register') }}" class="btn btn-accent ml-1 lg:ml-2">Daftar</a>
        </nav>

        {{-- Menu ponsel: panel selebar layar, tautan setinggi 48px. --}}
        <details class="group md:hidden" data-dropdown>
            <summary class="-mr-2 flex size-11 cursor-pointer list-none items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 [&::-webkit-details-marker]:hidden" aria-label="Buka menu">
                <svg class="size-6 group-open:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg class="hidden size-6 group-open:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </summary>
            {{-- Tinggi eksplisit: header memakai backdrop-blur sehingga menjadi containing block untuk elemen fixed. --}}
            <div class="fixed inset-x-0 top-16 h-[calc(100dvh-4rem)] overflow-y-auto border-t border-slate-200 bg-white px-4 pt-3 pb-[calc(6rem+env(safe-area-inset-bottom))]">
                <nav aria-label="Menu utama (seluler)">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($menu as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @class(['flex min-h-12 items-center justify-between text-base font-medium', 'text-brand-700' => request()->routeIs($item['active']), 'text-slate-800' => ! request()->routeIs($item['active'])])
                                   @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                    <svg class="size-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.3 14.7a1 1 0 0 1 0-1.4L10.6 10 7.3 6.7a1 1 0 0 1 1.4-1.4l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 0 1-1.4 0z" clip-rule="evenodd"/></svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($about->isNotEmpty())
                        <p class="mt-5 text-xs font-semibold tracking-wide text-slate-500 uppercase">Tentang Kami</p>
                        <ul class="mt-1 divide-y divide-slate-100">
                            @foreach ($about as $slug => $label)
                                <li>
                                    <a href="{{ route('pages.show', $slug) }}"
                                       @class(['flex min-h-12 items-center justify-between text-base font-medium', 'text-brand-700' => $currentPage === $slug, 'text-slate-800' => $currentPage !== $slug])
                                       @if ($currentPage === $slug) aria-current="page" @endif>
                                        {{ $label }}
                                        <svg class="size-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.3 14.7a1 1 0 0 1 0-1.4L10.6 10 7.3 6.7a1 1 0 0 1 1.4-1.4l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 0 1-1.4 0z" clip-rule="evenodd"/></svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </nav>
                <div class="mt-5 grid gap-2">
                    <a href="{{ route('register') }}" class="btn btn-accent min-h-12 text-base">Daftar Bimtek</a>
                    <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin informasi Bimtek & Diklat.') }}" target="_blank" rel="noopener" class="btn btn-wa min-h-12 text-base">Chat WhatsApp</a>
                    @if ($phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="btn btn-outline min-h-12 text-base">Telepon {{ $phone }}</a>
                    @endif
                </div>
            </div>
        </details>
    </div>
</header>
