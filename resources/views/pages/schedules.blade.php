<x-layout title="Jadwal Bimtek {{ now()->year }}" description="Jadwal Bimtek & Diklat terbaru di berbagai kota di Indonesia. Filter berdasarkan kota, bulan, tahun dan mode pelaksanaan, lalu daftar online.">
    <x-slot:head>
        <x-schedule-jsonld :schedules="\App\Models\Schedule::upcoming()->with(['city', 'venue', 'topic'])->limit(50)->get()" />
    </x-slot:head>

    <x-page-header title="Jadwal Bimtek & Diklat" subtitle="Jadwal bersifat umum: peserta bebas memilih materi saat mendaftar." :breadcrumbs="['Jadwal' => null]" />

    <div class="container-page mt-6 sm:mt-8">
        @include('partials.registration-info')
        <livewire:schedule-filter />
    </div>
</x-layout>
