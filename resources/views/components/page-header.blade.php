@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

<section class="bg-gradient-to-br from-brand-800 to-brand-950 text-white">
    <div class="container-page py-7 sm:py-14">
        @if ($breadcrumbs)
            <x-jsonld :data="\App\Support\Seo::breadcrumbs($breadcrumbs)" />
            <nav aria-label="Breadcrumb" class="mb-3 text-sm text-brand-100">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('home') }}" class="hover:text-white">Beranda</a></li>
                    @foreach ($breadcrumbs as $label => $url)
                        {{-- Di ponsel, crumb halaman aktif disembunyikan karena sudah tampil sebagai judul. --}}
                        <li aria-hidden="true" @class(['hidden sm:list-item' => ! $url])>/</li>
                        <li @class(['hidden sm:list-item' => ! $url])>
                            @if ($url)
                                <a href="{{ $url }}" class="hover:text-white">{{ $label }}</a>
                            @else
                                <span class="text-white" aria-current="page">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="text-2xl leading-tight font-bold tracking-tight text-balance sm:text-4xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-3 max-w-3xl text-brand-100">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
