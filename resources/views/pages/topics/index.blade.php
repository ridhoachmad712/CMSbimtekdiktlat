<x-layout title="Materi Bimtek" description="Katalog materi Bimtek & Diklat: keuangan daerah, kepegawaian, aset, pengadaan barang/jasa, desa, DPRD, BLUD dan lainnya.">
    <x-page-header title="Materi Bimtek & Diklat" subtitle="Pilih kategori untuk melihat daftar materi. Materi dapat dipilih pada jadwal mana pun." :breadcrumbs="['Materi' => null]" />

    <div class="container-page mt-8 grid gap-6 md:grid-cols-2">
        @foreach ($categories as $category)
            <section class="card p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">
                        <a href="{{ route('topics.category', $category) }}" class="hover:text-brand-700">{{ $category->name }}</a>
                    </h2>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $category->topics->count() }} materi</span>
                </div>
                @if ($category->description)
                    <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($category->description), 140) }}</p>
                @endif
                @if ($category->topics->isNotEmpty())
                    <ul class="mt-4 space-y-1.5 text-sm">
                        @foreach ($category->topics->take(5) as $topic)
                            <li><a href="{{ route('topics.show', [$category, $topic]) }}" class="text-brand-700 hover:underline">{{ $topic->title }}</a></li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('topics.category', $category) }}" class="mt-4 inline-block text-sm font-semibold text-brand-700 hover:underline">Lihat kategori →</a>
            </section>
        @endforeach
    </div>
</x-layout>
