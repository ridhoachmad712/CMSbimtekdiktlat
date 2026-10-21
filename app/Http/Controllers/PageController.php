<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(string $page): View|RedirectResponse
    {
        $model = Page::where('slug', $page)->where('is_published', true)->first();

        if (! $model) {
            // Alamat artikel WordPress lama (/{slug}/) diarahkan permanen ke /artikel/{slug},
            // termasuk alamat lama yang dulu dialihkan Rank Math (config/legacy.php).
            $wpSlug = config('legacy.post_aliases')[$page] ?? $page;
            $post = Post::published()->where('wp_slug', $wpSlug)->first();
            abort_unless($post, 404);

            return redirect()->route('posts.show', $post, 301);
        }

        return view('pages.page', ['page' => $model]);
    }
}
