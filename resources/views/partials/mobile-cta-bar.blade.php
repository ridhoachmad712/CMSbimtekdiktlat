{{-- Bar ajakan tetap di bawah layar ponsel: Daftar + WhatsApp selalu terjangkau ibu jari. --}}
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-3 pt-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] shadow-[0_-4px_16px_rgba(15,23,42,0.08)] backdrop-blur md:hidden" aria-label="Aksi cepat">
    <div class="grid grid-cols-2 gap-2">
        <a href="{{ route('register', $registerParams ?? []) }}" class="btn btn-accent min-h-12 text-base">
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.5 2A1.5 1.5 0 0 0 4 3.5v13A1.5 1.5 0 0 0 5.5 18h9a1.5 1.5 0 0 0 1.5-1.5V7.4a1.5 1.5 0 0 0-.44-1.06l-3.9-3.9A1.5 1.5 0 0 0 10.6 2H5.5zm1.75 8.5h5.5a.75.75 0 0 1 0 1.5h-5.5a.75.75 0 0 1 0-1.5zm0 3h5.5a.75.75 0 0 1 0 1.5h-5.5a.75.75 0 0 1 0-1.5z"/></svg>
            Daftar
        </a>
        <a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin informasi Bimtek & Diklat.') }}" target="_blank" rel="noopener" class="btn btn-wa min-h-12 text-base">
            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.93 9.93 0 0 0-8.6 14.9L2 22l5.25-1.38A9.93 9.93 0 1 0 12.04 2zm5.82 14.13c-.25.7-1.44 1.34-2 1.42-.51.08-1.16.11-1.87-.12-.43-.14-.98-.32-1.69-.63-2.98-1.29-4.93-4.29-5.08-4.49-.14-.2-1.21-1.62-1.21-3.08s.77-2.18 1.04-2.48c.28-.3.6-.37.8-.37h.57c.18.01.43-.07.67.51.25.6.85 2.06.92 2.21.08.15.13.32.03.52-.1.2-.15.33-.3.5-.15.17-.32.39-.45.52-.15.15-.3.31-.13.61.17.3.77 1.28 1.66 2.07 1.14 1.02 2.1 1.33 2.4 1.48.3.15.48.13.65-.07.17-.2.75-.87.94-1.17.2-.3.4-.25.67-.15.27.1 1.73.82 2.03.97.3.15.5.22.57.35.08.13.08.72-.17 1.42z"/></svg>
            WhatsApp
        </a>
    </div>
</nav>
