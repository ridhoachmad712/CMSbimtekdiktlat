<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::active()->hasActiveTopics()->get();

        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('schedules'), 'priority' => '0.9'],
            ['loc' => route('topics.index'), 'priority' => '0.8'],
            ['loc' => route('register'), 'priority' => '0.8'],
            ['loc' => route('cities.index'), 'priority' => '0.7'],
            ['loc' => route('packages'), 'priority' => '0.7'],
            ['loc' => route('posts.index'), 'priority' => '0.6'],
        ])
            ->merge($categories->map(fn ($c) => ['loc' => route('topics.category', $c), 'lastmod' => $c->updated_at, 'priority' => '0.7']))
            ->merge(
                Topic::active()->with('category')->whereIn('category_id', $categories->modelKeys())->get()
                    ->map(fn ($t) => ['loc' => route('topics.show', [$t->category, $t]), 'lastmod' => $t->updated_at, 'priority' => '0.6'])
            )
            ->merge(City::active()->get()->map(fn ($c) => ['loc' => route('cities.show', $c), 'lastmod' => $c->updated_at, 'priority' => '0.6']))
            ->merge(Post::published()->get()->map(fn ($p) => ['loc' => route('posts.show', $p), 'lastmod' => $p->updated_at, 'priority' => '0.5']))
            ->merge(Page::where('is_published', true)->get()->map(fn ($p) => ['loc' => route('pages.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.4']));

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
