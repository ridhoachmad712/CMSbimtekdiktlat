<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/jadwal', 'pages.schedules')->name('schedules');
Route::view('/daftar', 'pages.register')->name('register');

Route::get('/materi', [TopicController::class, 'index'])->name('topics.index');
Route::get('/materi/{category}', [TopicController::class, 'category'])->name('topics.category');
Route::get('/materi/{category}/{topic}', [TopicController::class, 'show'])->name('topics.show')->scopeBindings();

Route::get('/kota', [CityController::class, 'index'])->name('cities.index');
Route::get('/kota/{city}', [CityController::class, 'show'])->name('cities.show');

Route::get('/paket', PackageController::class)->name('packages');

Route::get('/artikel', [PostController::class, 'index'])->name('posts.index');
Route::get('/artikel/kategori/{category}', [PostController::class, 'index'])->name('posts.category');
// Pratinjau draf/terjadwal dari panel admin (tautan bertanda tangan, berlaku sementara).
Route::get('/artikel/pratinjau/{post:id}', [PostController::class, 'preview'])->name('posts.preview')->middleware('signed');
Route::get('/artikel/{post}', [PostController::class, 'show'])->name('posts.show');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(implode("\n", [
    'User-agent: *',
    'Disallow: /admin',
    'Disallow: /livewire',
    '',
    'Sitemap: '.route('sitemap'),
    '',
]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('robots');

// Halaman situs lama (bimtekdiklat.com) → padanannya di situs ini.
Route::permanentRedirect('/hubungi-kami', '/kontak');
Route::permanentRedirect('/blog', '/artikel');
Route::permanentRedirect('/cara-pesan', '/daftar');
Route::get('/product/{slug}', fn (string $slug) => redirect()->route('cities.show', ['city' => $slug === 'denpasar' ? 'bali' : $slug], 301))
    ->where('slug', '[a-z0-9-]+');

// Arsip WordPress lama (kategori, tag, penulis) → daftar artikel.
Route::permanentRedirect('/category/{any}', '/artikel')->where('any', '.*');
Route::permanentRedirect('/tag/{any}', '/artikel')->where('any', '.*');
Route::permanentRedirect('/author/{any}', '/artikel')->where('any', '.*');

// Harus paling akhir: halaman statis (tentang-kami, legalitas, kontak, ...).
Route::get('/{page}', PageController::class)
    ->where('page', '(?!(admin|livewire|filament|storage|up)$)[a-z0-9-]+')
    ->name('pages.show');
