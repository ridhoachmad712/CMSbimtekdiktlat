@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'type' => 'website',
    'robots' => null,
    'registerParams' => [],
])

@php
    use Illuminate\Support\Str;

    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $tagline = \App\Models\Setting::get('site_tagline');

    // <title> ideal ≤ 60 karakter agar tidak terpotong di hasil pencarian ponsel.
    if ($title) {
        $fullTitle = $title.' | '.$siteName;
        if (mb_strlen($fullTitle) > 60) {
            $fullTitle = mb_strlen($title) <= 60 ? $title : Str::beforeLast(mb_substr($title, 0, 58), ' ').'…';
        }
    } else {
        $fullTitle = 'Jadwal Bimtek & Diklat ASN '.now()->year.' | '.$siteName;
    }

    $metaDescription = Str::limit(Str::squish(strip_tags($description ?? $tagline ?? '')), 158);

    // Halaman 2, 3, … memakai canonical sendiri agar artikel lama tetap ditemukan Google.
    $page = (int) request()->query('page', 1);
    $canonical ??= url()->current().($page > 1 ? '?page='.$page : '');
    $image ??= asset('images/og-default.png');

    $showMobileBar = ! request()->routeIs('register');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?: $fullTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $image }}">

    <meta name="theme-color" content="#6c0a28">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('images/icon-192.png') }}" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body @class(['flex min-h-screen flex-col bg-slate-50 font-sans text-slate-800 antialiased', 'pb-[calc(4.5rem+env(safe-area-inset-bottom))] md:pb-0' => $showMobileBar])>
    @include('partials.header', ['siteName' => $siteName])

    <main class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer', ['siteName' => $siteName, 'tagline' => $tagline])
    @if ($showMobileBar)
        @include('partials.mobile-cta-bar', ['registerParams' => $registerParams])
    @endif
    @include('partials.whatsapp-float', ['mobile' => ! $showMobileBar])
</body>
</html>
