<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\View\View;

class CityController extends Controller
{
    public function index(): View
    {
        return view('pages.cities.index', [
            'cities' => City::active()->withCount(['schedules' => fn ($q) => $q->upcoming()])->get(),
        ]);
    }

    public function show(City $city): View
    {
        abort_unless($city->is_active, 404);

        return view('pages.cities.show', [
            'city' => $city,
            'schedules' => $city->schedules()->upcoming()->with(['city', 'venue', 'topic'])->limit(30)->get(),
            'venues' => $city->venues()->orderBy('name')->get(),
            'otherCities' => City::active()->whereKeyNot($city->id)->get(),
        ]);
    }
}
