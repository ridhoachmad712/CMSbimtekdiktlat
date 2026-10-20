<x-layout :register-params="['materi' => $topic->slug]" :title="$topic->meta_title ?: $topic->title" :description="$topic->meta_description ?: ($topic->summary ?: 'Bimtek '.$topic->title.' — jadwal, sasaran peserta, dan pendaftaran online.')" type="article">
    <x-slot:head>
        <x-schedule-jsonld :schedules="$schedules" />
        <x-jsonld :data="\App\Support\Seo::course($topic)" />
    </x-slot:head>

    <x-page-header :title="$topic->title" :subtitle="$topic->summary" :breadcrumbs="['Materi' => route('topics.index'), $category->name => route('topics.category', $category), $topic->title => null]" />

    <div class="container-page mt-8 grid gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            @if ($topic->content)
                <section class="card p-6">
                    <x-rich-content :content="$topic->content" />
                </section>
            @endif

            @if ($topic->target_participants || $topic->legal_basis)
                <section class="grid gap-4 sm:grid-cols-2">
                    @if ($topic->target_participants)
                        <div class="card p-5">
                            <h2 class="font-semibold text-slate-900">Sasaran Peserta</h2>
                            <p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $topic->target_participants }}</p>
                        </div>
                    @endif
                    @if ($topic->legal_basis)
                        <div class="card p-5">
                            <h2 class="font-semibold text-slate-900">Dasar Hukum</h2>
                            <p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $topic->legal_basis }}</p>
                        </div>
                    @endif
                </section>
            @endif

            <section>
                <h2 class="section-title mb-4 text-xl sm:text-2xl">Jadwal Pelaksanaan</h2>
                @include('partials.schedule-table', ['schedules' => $schedules, 'topic' => $topic])
                <a href="{{ route('schedules') }}" class="mt-3 inline-block text-sm font-semibold text-brand-700 hover:underline">Lihat semua jadwal →</a>
            </section>

            @include('partials.share', ['title' => $topic->title])
        </div>

        <aside class="space-y-6">
            @include('partials.cta-box', [
                'registerParams' => ['materi' => $topic->slug],
                'waMessage' => "Halo, saya ingin informasi Bimtek \"{$topic->title}\".",
            ])
            @if ($related->isNotEmpty())
                <div class="card p-6">
                    <p class="font-semibold text-slate-900">Materi terkait</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($related as $item)
                            <li><a href="{{ route('topics.show', [$category, $item]) }}" class="text-brand-700 hover:underline">{{ $item->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-layout>
