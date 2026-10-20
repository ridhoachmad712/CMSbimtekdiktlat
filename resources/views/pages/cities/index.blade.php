<x-layout title="Kota Pelaksanaan Bimtek" description="Daftar kota pelaksanaan Bimtek & Diklat di seluruh Indonesia, dari Banda Aceh hingga Jayapura.">
    <x-page-header title="Kota Pelaksanaan" subtitle="Bimtek & Diklat dilaksanakan rutin di kota-kota berikut." :breadcrumbs="['Kota' => null]" />

    <div class="container-page mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($cities as $city)
            <a href="{{ route('cities.show', $city) }}" class="card group p-4 transition hover:border-brand-500">
                <p class="font-semibold text-slate-900 group-hover:text-brand-700">Bimtek {{ $city->name }}</p>
                <p class="text-sm text-slate-500">{{ $city->province }}</p>
                <p class="mt-2 text-xs text-slate-600">{{ $city->schedules_count ? $city->schedules_count.' jadwal mendatang' : 'Jadwal by request' }}</p>
            </a>
        @endforeach
    </div>
</x-layout>
