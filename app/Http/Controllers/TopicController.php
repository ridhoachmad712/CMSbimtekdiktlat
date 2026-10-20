<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Schedule;
use App\Models\Topic;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(): View
    {
        return view('pages.topics.index', [
            'categories' => Category::active()
                ->hasActiveTopics()
                ->with(['topics' => fn ($q) => $q->active()->orderBy('title')])
                ->get(),
        ]);
    }

    public function category(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('pages.topics.category', [
            'category' => $category,
            'topics' => $category->topics()->active()->orderByDesc('is_featured')->orderBy('title')->get(),
        ]);
    }

    public function show(Category $category, Topic $topic): View
    {
        abort_unless($category->is_active && $topic->is_active, 404);

        return view('pages.topics.show', [
            'category' => $category,
            'topic' => $topic,
            // Jadwal umum (tanpa materi) juga berlaku untuk materi ini.
            'schedules' => Schedule::upcoming()
                ->where(fn ($q) => $q->whereNull('topic_id')->orWhere('topic_id', $topic->id))
                ->with(['city', 'venue', 'topic'])
                ->limit(10)
                ->get(),
            'related' => $category->topics()->active()->whereKeyNot($topic->id)->orderBy('title')->limit(6)->get(),
        ]);
    }
}
