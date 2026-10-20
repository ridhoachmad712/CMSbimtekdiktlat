@php
    use App\Models\Setting;
    use App\Support\WhatsApp;

    $phone = Setting::get('phone');
    $email = Setting::get('email');
    $address = Setting::get('address');
    $social = array_filter((array) Setting::get('social_links', []));
    $footerPages = \App\Models\Page::where('is_published', true)->orderBy('title')->get(['title', 'slug']);
@endphp

<footer class="mt-16 bg-brand-950 text-slate-300">
    <div class="container-page grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <p class="text-lg font-bold text-white">{{ $siteName }}</p>
            @if ($tagline)
                <p class="mt-2 max-w-md text-sm leading-relaxed">{{ $tagline }}</p>
            @endif
            <a href="{{ WhatsApp::link('Halo, saya ingin bertanya tentang jadwal Bimtek.') }}" target="_blank" rel="noopener"
               class="btn btn-wa mt-5">Hubungi via WhatsApp</a>
        </div>

        <div>
            <p class="font-semibold text-white">Kontak</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li>WhatsApp: <a href="{{ WhatsApp::link() }}" class="hover:text-white" target="_blank" rel="noopener">+{{ WhatsApp::number() }}</a></li>
                @if ($phone)
                    <li>Telepon: <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="hover:text-white">{{ $phone }}</a></li>
                @endif
                @if ($email)
                    <li>Email: <a href="mailto:{{ $email }}" class="hover:text-white">{{ $email }}</a></li>
                @endif
                @if ($address)
                    <li>{{ $address }}</li>
                @endif
            </ul>
            @if ($social)
                <ul class="mt-4 flex flex-wrap gap-3 text-sm">
                    @foreach ($social as $network => $url)
                        <li><a href="{{ $url }}" target="_blank" rel="noopener" class="capitalize hover:text-white">{{ $network }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <p class="font-semibold text-white">Tautan</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('schedules') }}" class="hover:text-white">Jadwal Bimtek</a></li>
                <li><a href="{{ route('topics.index') }}" class="hover:text-white">Materi Bimtek</a></li>
                <li><a href="{{ route('packages') }}" class="hover:text-white">Paket & Biaya</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-white">Form Pendaftaran</a></li>
                @foreach ($footerPages as $page)
                    <li><a href="{{ route('pages.show', $page->slug) }}" class="hover:text-white">{{ $page->title }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="container-page py-5 text-xs text-slate-400">&copy; {{ now()->year }} {{ $siteName }}. Hak cipta dilindungi.</p>
    </div>
</footer>
