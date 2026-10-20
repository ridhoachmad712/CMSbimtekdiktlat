<x-layout title="Halaman Tidak Ditemukan">
    <section class="container-page py-20 text-center">
        <p class="text-sm font-semibold uppercase tracking-wider text-brand-700">Error 404</p>
        <h1 class="mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">Halaman tidak ditemukan</h1>
        <p class="mx-auto mt-4 max-w-xl text-slate-600">
            Halaman yang Anda cari mungkin sudah dipindahkan atau alamatnya salah.
            Silakan lihat jadwal terbaru atau hubungi kami via WhatsApp.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('schedules') }}" class="btn btn-primary">Lihat Jadwal</a>
            <a href="{{ route('topics.index') }}" class="btn btn-outline">Katalog Materi</a>
            <a href="{{ route('home') }}" class="btn btn-outline">Beranda</a>
        </div>
    </section>
</x-layout>
