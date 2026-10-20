<x-layout :title="'Bimtek '.$city->name.' '.now()->year" :description="$city->description ?: 'Jadwal Bimtek & Diklat di '.$city->name.($city->province && $city->province !== $city->name ? ', '.$city->province : '').' tahun '.now()->year.'. Pilih tanggal dan materi, daftar online atau via WhatsApp.'">
    <x-slot:head>
        <x-schedule-jsonld :schedules="$schedules" />
    </x-slot:head>

    <x-page-header :title="'Bimtek '.$city->name" :subtitle="'Jadwal Bimtek & Diklat di '.$city->name.($city->province && $city->province !== $city->name ? ', '.$city->province : '').'. Materi dapat dipilih saat mendaftar.'" :breadcrumbs="['Kota' => route('cities.index'), $city->name => null]" />

    <div class="container-page mt-6 grid gap-8 sm:mt-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            <section>
                <h2 class="section-title mb-4 text-xl sm:text-2xl">Jadwal di {{ $city->name }}</h2>
                @include('partials.schedule-table', ['schedules' => $schedules, 'hideCity' => true])
            </section>

            @if ($city->description)
                <section class="card p-5 sm:p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Bimtek & Diklat di {{ $city->name }}</h2>
                    <div class="mt-2 space-y-3 text-slate-700">
                        @foreach (preg_split('/\R{2,}/', trim($city->description)) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($venues->isNotEmpty())
                <section>
                    <h2 class="section-title mb-4 text-xl sm:text-2xl">Lokasi / Hotel</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($venues as $venue)
                            <div class="card p-4">
                                <p class="font-semibold text-slate-900">{{ $venue->name }}</p>
                                @if ($venue->address)
                                    <p class="mt-1 text-sm text-slate-600">{{ $venue->address }}</p>
                                @endif
                                @if ($venue->map_url)
                                    <a href="{{ $venue->map_url }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm text-brand-700 hover:underline">Lihat peta</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section>
                <h2 class="section-title mb-4 text-xl sm:text-2xl">Kota lainnya</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($otherCities as $other)
                        <a href="{{ route('cities.show', $other) }}" class="chip">{{ $other->name }}</a>
                    @endforeach
                </div>
            </section>
        </div>
        <aside class="hidden lg:block">
            @include('partials.cta-box', [
                'heading' => 'Bimtek di '.$city->name,
                'waMessage' => "Halo, saya ingin informasi jadwal Bimtek di {$city->name}.",
            ])
        </aside>
    </div>
</x-layout>
