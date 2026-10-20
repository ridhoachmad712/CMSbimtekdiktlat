@php
    // Kotak ajakan disisipkan di tengah artikel panjang (sebelum subjudul tengah).
    $html = $article['html'];
    $toc = $article['toc'];
    $before = $html;
    $after = '';
    if (count($toc) >= 4) {
        $marker = '<h2 id="'.$toc[intdiv(count($toc), 2)]['id'].'"';
        if (($pos = strpos($html, $marker)) !== false) {
            $before = substr($html, 0, $pos);
            $after = substr($html, $pos);
        }
    }
    $featured = $post->imageAttributes('(min-width: 1024px) 720px, 100vw');
    $crumbs = ['Artikel' => route('posts.index')];
    if ($post->category) {
        $crumbs[$post->category->name] = route('posts.category', $post->category);
    }
    $crumbs[\Illuminate\Support\Str::limit($post->title, 40)] = null;
@endphp

<x-layout :title="$post->meta_title ?: $post->title" :description="$post->meta_description ?: $post->excerpt" type="article" :image="$post->image_url" :robots="($preview ?? false) ? 'noindex, nofollow' : null">
    <x-slot:head>
        @unless ($preview ?? false)
            <x-jsonld :data="\App\Support\Seo::article($post)" />
        @endunless
        @if ($featured)
            <link rel="preload" as="image" href="{{ $featured['src'] }}" @if (isset($featured['srcset'])) imagesrcset="{{ $featured['srcset'] }}" imagesizes="{{ $featured['sizes'] }}" @endif>
        @endif
    </x-slot:head>

    <x-page-header :title="$post->title" :breadcrumbs="$crumbs">
        <p class="mt-3 text-sm text-brand-100">
            @if ($post->published_at)
                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->locale('id')->translatedFormat('d F Y') }}</time>
            @else
                Belum terbit
            @endif
            @if ($post->category) · <a href="{{ route('posts.category', $post->category) }}" class="underline-offset-2 hover:underline">{{ $post->category->name }}</a> @endif
        </p>
    </x-page-header>

    @if ($preview ?? false)
        <div class="border-b border-amber-200 bg-amber-50 text-sm text-amber-900">
            <p class="container-page py-3"><strong>Pratinjau</strong> — status artikel: {{ $post->status->getLabel() }}. Halaman ini belum tampil untuk pengunjung.</p>
        </div>
    @endif

    <div class="container-page mt-6 grid gap-8 sm:mt-8 lg:grid-cols-3">
        <article class="min-w-0 space-y-6 lg:col-span-2">
            @if ($featured)
                <img {{ new \Illuminate\View\ComponentAttributeBag($featured) }} alt="{{ $post->title }}" class="w-full rounded-xl" fetchpriority="high" decoding="async">
            @endif

            @if (count($toc) >= 3)
                <details class="card group p-4 sm:p-5">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                        Daftar Isi
                        <svg class="size-5 text-slate-500 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4z" clip-rule="evenodd"/></svg>
                    </summary>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-[15px]">
                        @foreach ($toc as $item)
                            <li><a href="#{{ $item['id'] }}" class="block py-1.5 text-brand-700 hover:underline">{{ $item['text'] }}</a></li>
                        @endforeach
                    </ol>
                </details>
            @endif

            <div class="card p-5 sm:p-8">
                <div class="rich-content">{!! $before !!}</div>
                @if ($after !== '')
                    @include('partials.inline-cta', ['topic' => $topics->first()])
                    <div class="rich-content mt-4">{!! $after !!}</div>
                @endif
            </div>

            @include('partials.inline-cta', ['topic' => $topics->first(), 'heading' => 'Siap mendaftar Bimtek?'])
            @include('partials.share', ['title' => $post->title])
        </article>

        <aside class="space-y-6">
            <div class="hidden lg:block">
                @include('partials.cta-box', ['heading' => 'Tertarik mengikuti Bimtek?'])
            </div>
            @if ($topics->isNotEmpty())
                <div class="card p-5 sm:p-6">
                    <p class="font-semibold text-slate-900">Materi Bimtek terkait</p>
                    <ul class="mt-2 divide-y divide-slate-100 text-sm">
                        @foreach ($topics as $topic)
                            <li><a href="{{ route('topics.show', [$topic->category, $topic]) }}" class="block py-2.5 text-brand-700 hover:underline">{{ $topic->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($related->isNotEmpty())
                <div class="card p-5 sm:p-6">
                    <p class="font-semibold text-slate-900">Artikel terkait</p>
                    <ul class="mt-2 divide-y divide-slate-100 text-sm">
                        @foreach ($related as $item)
                            <li><a href="{{ route('posts.show', $item) }}" class="block py-2.5 text-brand-700 hover:underline">{{ $item->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-layout>
