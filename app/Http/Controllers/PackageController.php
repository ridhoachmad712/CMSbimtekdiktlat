<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.packages', ['packages' => Package::active()->get()]);
    }
}
