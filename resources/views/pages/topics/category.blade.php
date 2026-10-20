<x-layout :title="$category->name" :description="$category->description ?: 'Daftar materi '.$category->name.' untuk ASN dan aparatur pemerintah. Jadwal rutin di berbagai kota.'">
    <x-page-header :title="$category->name" :subtitle="$category->description ? \Illuminate\Support\Str::limit(strip_tags($category->description), 220) : null" :breadcrumbs="['Materi' => route('topics.index'), $category->name => null]" />

    <div class="container-page mt-8 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($topics->isEmpty())
                <div class="card p-8 text-center text-slate-500">Materi untuk kategori ini segera tersedia. Hubungi kami untuk informasi lebih lanjut.</div>
            @else
                <ol class="card divide-y divide-slate-100">
                    @foreach ($topics as $topic)
                        <li class="flex items-start gap-4 p-4">
                            <span class="mt-0.5 w-6 shrink-0 text-right text-sm text-slate-400">{{ $loop->iteration }}.</span>
                            <div class="flex-1">
                                <a href="{{ route('topics.show', [$category, $topic]) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $topic->title }}</a>
                                @if ($topic->summary)
                                    <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($topic->summary, 160) }}</p>
                                @endif
                            </div>
                            <a href="{{ route('register', ['materi' => $topic->slug]) }}" class="hidden shrink-0 text-sm font-semibold text-brand-700 hover:underline sm:inline">Daftar</a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
        <aside>
            @include('partials.cta-box')
        </aside>
    </div>
</x-layout>
