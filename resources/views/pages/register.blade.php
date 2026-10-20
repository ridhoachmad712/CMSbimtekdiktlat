<x-layout title="Form Pendaftaran Bimtek" description="Daftar Bimtek & Diklat secara online. Pilih jadwal, kota dan materi, lalu lanjutkan konfirmasi via WhatsApp.">
    <x-page-header title="Form Pendaftaran" subtitle="Isi data di bawah ini. Setelah terkirim, Anda akan mendapat kode pendaftaran dan dapat langsung konfirmasi via WhatsApp." :breadcrumbs="['Daftar' => null]" />

    <div class="container-page mt-6 max-w-3xl sm:mt-8">
        @include('partials.registration-info', ['showPdf' => false])
        <livewire:registration-form />
    </div>
</x-layout>
