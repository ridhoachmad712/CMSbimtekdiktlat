<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteImprovementsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_uploaded_post_image_is_stored_on_public_disk(): void
    {
        Storage::fake('public');
        $this->actingAs(User::first());

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Artikel Bergambar',
                'image' => UploadedFile::fake()->image('sampul.jpg', 1200, 630),
                'published_at' => now()->subHour(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::where('title', 'Artikel Bergambar')->sole();
        Storage::disk('public')->assertExists($post->image);
        $this->assertStringContainsString('/storage/'.$post->image, $post->image_url);

        $this->get('/artikel')->assertSee($post->image_url, false);
        $this->get("/artikel/{$post->slug}")->assertSee('<meta property="og:image" content="'.$post->image_url.'">', false);
    }

    public function test_admin_resources_use_indonesian_labels(): void
    {
        $this->actingAs(User::first());

        $this->get('/admin/cities')->assertSee('Nama Kota')->assertSee('Provinsi')->assertDontSee('Sort order');
        $this->get('/admin/topics')->assertSee('Judul Materi')->assertDontSee('Is active');
        $this->get('/admin/packages')->assertSee('Harga per Peserta')->assertSee('Rp');
        $this->get('/admin/packages/create')->assertSee('Rp')->assertDontSee('>$<', false);
        $this->get('/admin/posts')->assertSee('Penulis')->assertDontSee('User id');
    }

    public function test_branded_404_page(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Lihat Jadwal');
    }

    public function test_standalone_error_pages_render_without_layout(): void
    {
        $html = view('errors.standalone', ['code' => 500, 'heading' => 'Terjadi gangguan pada server', 'message' => 'Coba lagi.'])->render();

        $this->assertStringContainsString('Error 500', $html);
        $this->assertStringNotContainsString('/build/', $html);

        $this->actingAs(User::factory()->role(UserRole::Cs)->create())
            ->get('/admin/schedules')
            ->assertForbidden()
            ->assertSee('Akses ditolak');
    }

    public function test_robots_txt_points_to_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_default_og_image_is_present(): void
    {
        $this->assertFileExists(public_path('images/og-default.png'));
        $this->get('/')->assertSee('<meta property="og:image" content="'.asset('images/og-default.png').'">', false);
    }

    public function test_empty_categories_are_hidden_publicly(): void
    {
        $empty = Category::create(['name' => 'Kategori Kosong Uji', 'is_active' => true]);

        $this->get('/')->assertDontSee('Kategori Kosong Uji');
        $this->get('/materi')->assertDontSee('Kategori Kosong Uji');
        $this->get('/sitemap.xml')->assertDontSee('/materi/'.$empty->slug.'<', false);

        $this->assertTrue(Category::active()->hasActiveTopics()->get()->every(fn ($c) => $c->topics()->where('is_active', true)->exists()));
    }
}
