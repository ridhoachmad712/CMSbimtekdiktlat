@props(['schedules'])

@if ($schedules->isNotEmpty())
    <x-jsonld :data="\App\Support\Seo::events(collect($schedules->all()))" />
@endif
