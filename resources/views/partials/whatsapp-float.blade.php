<a href="{{ \App\Support\WhatsApp::link('Halo, saya ingin informasi Bimtek & Diklat.') }}"
   target="_blank" rel="noopener"
   @class([
       'fixed right-4 bottom-4 z-50 items-center gap-2 rounded-full bg-wa py-3 pr-5 pl-3 font-semibold text-white shadow-lg shadow-black/20 transition hover:scale-105 hover:bg-wa-dark sm:right-6 sm:bottom-6',
       // Di ponsel diganti bar bawah (partials.mobile-cta-bar), kecuali di halaman daftar.
       'flex' => $mobile ?? false,
       'hidden md:flex' => ! ($mobile ?? false),
   ])
   aria-label="Hubungi kami via WhatsApp">
    <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.12-.27-.2-.57-.35zM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.21-3.58.94.96-3.49-.23-.36a9.43 9.43 0 0 1-1.45-5.03c0-5.22 4.25-9.47 9.48-9.47a9.4 9.4 0 0 1 6.7 2.78 9.4 9.4 0 0 1 2.77 6.7c0 5.22-4.25 9.46-9.47 9.46zm8.06-17.52A11.32 11.32 0 0 0 12.04.65C5.76.65.65 5.76.65 12.04c0 2 .52 3.96 1.52 5.69L.55 23.65l6.05-1.59a11.37 11.37 0 0 0 5.44 1.39h.01c6.27 0 11.38-5.11 11.39-11.39 0-3.04-1.19-5.9-3.34-8.06z"/></svg>
    <span class="hidden sm:inline">Hubungi Kami</span>
</a>
