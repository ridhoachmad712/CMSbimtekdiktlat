@php($img = $post->imageAttributes('(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw'))
<article class="card relative flex flex-col overflow-hidden">
    @if ($img)
        <img {{ new \Illuminate\View\ComponentAttributeBag($img) }} alt="" class="aspect-video w-full object-cover" loading="lazy" decoding="async">
    @endif
    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs text-slate-500">
            <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->locale('id')->translatedFormat('d F Y') }}</time>
            @if ($post->category) · {{ $post->category->name }} @endif
        </p>
        <h3 class="mt-2 line-clamp-3 font-semibold text-slate-900">
            {{-- Seluruh kartu dapat diketuk (target sentuh besar di ponsel). --}}
            <a href="{{ route('posts.show', $post) }}" class="after:absolute after:inset-0 hover:text-brand-700">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $post->excerpt }}</p>
        @endif
        <span class="mt-auto pt-4 text-sm font-semibold text-brand-700" aria-hidden="true">Baca selengkapnya →</span>
    </div>
</article>
