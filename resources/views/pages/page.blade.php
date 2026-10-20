<x-layout :title="$page->meta_title ?: $page->title" :description="$page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160)">
    <x-page-header :title="$page->title" :breadcrumbs="[$page->title => null]" />

    <div class="container-page mt-8 grid gap-8 lg:grid-cols-3">
        <article class="card p-6 sm:p-8 lg:col-span-2">
            <x-rich-content :content="$page->content" />
        </article>
        <aside>
            @include('partials.cta-box', ['heading' => 'Hubungi Kami'])
        </aside>
    </div>
</x-layout>
