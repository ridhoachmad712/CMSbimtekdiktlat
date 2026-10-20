<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Package;
use App\Models\Post;
use App\Models\Schedule;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'schedules' => Schedule::upcoming()->with(['city', 'venue', 'topic'])->limit(8)->get(),
            'categories' => Category::active()->hasActiveTopics()->withCount(['topics' => fn ($q) => $q->active()])->get(),
            'cities' => City::active()->get(),
            'packages' => Package::active()->get(),
            'posts' => Post::published()->with('category')->limit(3)->get(),
            'testimonials' => Testimonial::active()->limit(6)->get(),
        ]);
    }
}
