<?php

namespace App\Support;

use App\Enums\ScheduleMode;
use App\Models\Package;
use App\Models\Post;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Topic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Seo
{
    /**
     * Data schema.org `Event` untuk daftar jadwal.
     *
     * @param  Collection<int, Schedule>  $schedules
     */
    public static function events(Collection $schedules): array
    {
        $siteName = Setting::get('site_name', config('app.name'));
        $prices = Package::active()->pluck('price')->filter();

        return $schedules->map(function (Schedule $schedule) use ($prices) {
            $online = $schedule->mode === ScheduleMode::Online;

            $place = [
                '@type' => 'Place',
                'name' => $schedule->venue?->name ?? $schedule->city->name,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $schedule->venue?->address,
                    'addressLocality' => $schedule->city->name,
                    'addressRegion' => $schedule->city->province,
                    'addressCountry' => 'ID',
                ]),
            ];
            $virtual = ['@type' => 'VirtualLocation', 'url' => route('register', ['jadwal' => $schedule->id])];

            return array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                // Nama unik per jadwal (materi/kota/tanggal) agar tidak dianggap duplikat.
                'name' => ($schedule->topic?->title ?? 'Bimtek & Diklat').' — '.$schedule->city->name.' '.$schedule->date_range,
                'image' => [static::defaultImage()],
                'description' => 'Bimbingan teknis & diklat untuk ASN/aparatur pemerintah di '.$schedule->city->name.'.',
                'startDate' => $schedule->start_date->toDateString(),
                'endDate' => $schedule->end_date->toDateString(),
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => match ($schedule->mode) {
                    ScheduleMode::Online => 'https://schema.org/OnlineEventAttendanceMode',
                    ScheduleMode::Hybrid => 'https://schema.org/MixedEventAttendanceMode',
                    default => 'https://schema.org/OfflineEventAttendanceMode',
                },
                'location' => match ($schedule->mode) {
                    ScheduleMode::Online => $virtual,
                    ScheduleMode::Hybrid => [$place, $virtual],
                    default => $place,
                },
                'organizer' => static::organizationRef(),
                'offers' => $prices->isEmpty() ? null : [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'IDR',
                    'lowPrice' => $prices->min(),
                    'highPrice' => $prices->max(),
                    'availability' => 'https://schema.org/InStock',
                    'url' => route('register', ['jadwal' => $schedule->id]),
                ],
            ]);
        })->values()->all();
    }

    /** JSON-LD siap tempel; dirender lewat <x-jsonld> (bukan Blade) agar "@context" tidak terbaca sebagai direktif. */
    public static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    public static function siteName(): string
    {
        return (string) Setting::get('site_name', config('app.name'));
    }

    public static function logoUrl(): string
    {
        $logo = Setting::get('logo');

        return $logo ? Storage::disk('public')->url($logo) : asset('images/logo.png');
    }

    public static function defaultImage(): string
    {
        return asset('images/og-default.png');
    }

    /** @return array<string, mixed> */
    public static function organizationRef(): array
    {
        return ['@type' => 'Organization', '@id' => url('/').'#organization', 'name' => static::siteName(), 'url' => url('/')];
    }

    /** Organization + WebSite untuk beranda. */
    public static function organization(): array
    {
        $phone = Setting::get('phone') ?: '+'.WhatsApp::number();

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'EducationalOrganization',
                    '@id' => url('/').'#organization',
                    'name' => static::siteName(),
                    'description' => Setting::get('site_tagline'),
                    'url' => url('/'),
                    'logo' => static::logoUrl(),
                    'image' => static::defaultImage(),
                    'telephone' => $phone,
                    'email' => Setting::get('email') ?: null,
                    'address' => Setting::get('address') ? ['@type' => 'PostalAddress', 'streetAddress' => Setting::get('address'), 'addressCountry' => 'ID'] : null,
                    'sameAs' => array_values(array_filter((array) Setting::get('social_links', []))) ?: null,
                    'contactPoint' => ['@type' => 'ContactPoint', 'telephone' => '+'.WhatsApp::number(), 'contactType' => 'customer service', 'availableLanguage' => 'Indonesian'],
                ]),
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => static::siteName(),
                    'inLanguage' => 'id-ID',
                    'publisher' => ['@id' => url('/').'#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('posts.index').'?q={search_term_string}'],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }

    public static function article(Post $post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => Str::limit($post->title, 110, ''),
            'description' => $post->meta_description ?: $post->excerpt,
            'image' => [$post->image_url ?: static::defaultImage()],
            'datePublished' => $post->published_at->toAtomString(),
            'dateModified' => $post->updated_at->toAtomString(),
            'author' => static::organizationRef(),
            'publisher' => static::organizationRef() + ['logo' => ['@type' => 'ImageObject', 'url' => static::logoUrl()]],
            'articleSection' => $post->category?->name,
            'inLanguage' => 'id-ID',
            'mainEntityOfPage' => route('posts.show', $post),
        ]);
    }

    public static function course(Topic $topic): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $topic->title,
            'description' => $topic->summary ?: $topic->title,
            'inLanguage' => 'id-ID',
            'provider' => static::organizationRef(),
        ]);
    }

    /** @param  array<string, string|null>  $crumbs  label => url (null = halaman ini) */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = ['Beranda' => route('home')] + $crumbs;
        $position = 0;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->map(fn ($url, $label) => array_filter([
                '@type' => 'ListItem',
                'position' => ++$position,
                'name' => $label,
                'item' => $url ?? url()->current(),
            ]))->values()->all(),
        ];
    }
}
