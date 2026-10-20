@props(['data'])

@if (filled($data))
    <script type="application/ld+json">{!! \App\Support\Seo::encode($data) !!}</script>
@endif
