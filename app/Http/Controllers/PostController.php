<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request, ?Category $category = null): View
    {
        abort_if($category && ! $category->is_active, 404);
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);

        $posts = Post::published()
            ->with('category')
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")))
            ->paginate(12)
            ->withQueryString();

        return view('pages.posts.index', [
            'posts' => $posts,
            'category' => $category,
            'search' => $search,
            'categories' => Category::active()->whereHas('posts', fn ($q) => $q->published())->withCount(['posts' => fn ($q) => $q->published()])->get(),
        ]);
    }

    public function show(Post $post): View
    {
        abort_if(is_null($post->published_at) || $post->published_at->isFuture(), 404);

        return $this->article($post);
    }

    /** Pratinjau artikel draf/terjadwal (route bertanda tangan, tidak diindeks). */
    public function preview(Post $post): View
    {
        return $this->article($post, preview: true);
    }

    private function article(Post $post, bool $preview = false): View
    {
        $related = Post::published()
            ->whereKeyNot($post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->limit(5)
            ->get();
        if ($related->count() < 3) {
            $related = $related->merge(Post::published()->whereKeyNot($post->id)->whereNotIn('id', $related->pluck('id'))->limit(5 - $related->count())->get());
        }

        return view('pages.posts.show', [
            'post' => $post->load(['category', 'author']),
            'preview' => $preview,
            'article' => $post->renderedContent(),
            'related' => $related,
            'topics' => $post->category_id
                ? Topic::active()->where('category_id', $post->category_id)->with('category')->orderByDesc('is_featured')->limit(5)->get()
                : collect(),
        ]);
    }
}
