@php
    $heading = $category ? 'Artikel '.$category->name : 'Artikel';
    $crumbs = $category ? ['Artikel' => route('posts.index'), $category->name => null] : ['Artikel' => null];
    $description = $category
        ? 'Kumpulan artikel '.$category->name.': regulasi, materi, dan informasi pelatihan untuk aparatur pemerintah.'
        : 'Artikel, informasi regulasi, dan berita seputar Bimtek & Diklat untuk aparatur pemerintah.';
@endphp

<x-layout :title="$heading.($posts->currentPage() > 1 ? ' — Halaman '.$posts->currentPage() : '')" :description="$description" :robots="$search !== '' ? 'noindex, follow' : null">
    <x-page-header :title="$heading" :subtitle="$category?->description ?: 'Informasi regulasi dan kegiatan Bimtek & Diklat.'" :breadcrumbs="$crumbs" />

    <div class="container-page mt-6 sm:mt-8">
        <form action="{{ $category ? route('posts.category', $category) : route('posts.index') }}" method="get" role="search" class="flex gap-2">
            <label for="q" class="sr-only">Cari artikel</label>
            <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari artikel, mis. SKP, APBD…" class="form-input" enterkeyhint="search">
            <button type="submit" class="btn btn-primary min-h-11 shrink-0">Cari</button>
        </form>

        @if ($categories->isNotEmpty())
            {{-- Chip kategori: geser horizontal di ponsel. --}}
            <nav aria-label="Kategori artikel" class="-mx-4 mt-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul class="flex gap-2 pb-2 sm:flex-wrap">
                    <li><a href="{{ route('posts.index') }}" @class(['chip', 'chip-active' => ! $category])>Semua</a></li>
                    @foreach ($categories as $item)
                        <li><a href="{{ route('posts.category', $item) }}" @class(['chip', 'chip-active' => $category?->is($item)])>{{ $item->name }} <span class="text-xs opacity-70">{{ $item->posts_count }}</span></a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($search !== '')
            <p class="mt-4 text-sm text-slate-600">{{ $posts->total() }} artikel untuk “{{ $search }}”. <a href="{{ url()->current() }}" class="font-semibold text-brand-700 hover:underline">Hapus pencarian</a></p>
        @endif

        @if ($posts->isEmpty())
            <div class="card mt-6 p-8 text-center text-slate-500">Belum ada artikel yang cocok.</div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
            <div class="mt-8">{{ $posts->onEachSide(1)->links() }}</div>
        @endif
    </div>
</x-layout>
