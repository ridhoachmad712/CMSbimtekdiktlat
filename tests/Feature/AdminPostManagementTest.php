<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Registrations\RegistrationResource;
use App\Filament\Resources\Topics\TopicResource;
use App\Filament\Widgets\PostStats;
use App\Models\Category;
use App\Models\Post;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPostManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Post::query()->delete();
        $this->actingAs(User::first());
    }

    private function makePost(array $attributes = []): Post
    {
        return Post::create(['title' => 'Artikel '.uniqid(), 'content' => '<p>Isi</p>', ...$attributes]);
    }

    public function test_status_is_derived_from_published_at(): void
    {
        $this->assertSame(PostStatus::Draft, $this->makePost()->status);
        $this->assertSame(PostStatus::Scheduled, $this->makePost(['published_at' => now()->addDay()])->status);
        $this->assertSame(PostStatus::Published, $this->makePost(['published_at' => now()->subDay()])->status);
    }

    public function test_list_tabs_filter_by_status_and_category(): void
    {
        $draft = $this->makePost();
        $scheduled = $this->makePost(['published_at' => now()->addDay(), 'category_id' => Category::value('id')]);
        $published = $this->makePost(['published_at' => now()->subDay(), 'category_id' => Category::value('id')]);

        Livewire::test(ListPosts::class)
            ->assertCanSeeTableRecords([$draft, $scheduled, $published])
            ->set('activeTab', 'draft')->assertCanSeeTableRecords([$draft])->assertCanNotSeeTableRecords([$scheduled, $published])
            ->set('activeTab', 'scheduled')->assertCanSeeTableRecords([$scheduled])->assertCanNotSeeTableRecords([$draft, $published])
            ->set('activeTab', 'published')->assertCanSeeTableRecords([$published])->assertCanNotSeeTableRecords([$draft, $scheduled])
            ->set('activeTab', 'uncategorized')->assertCanSeeTableRecords([$draft])->assertCanNotSeeTableRecords([$scheduled, $published]);
    }

    public function test_bulk_actions_change_category_and_status(): void
    {
        $posts = collect([$this->makePost(), $this->makePost()]);
        $category = Category::where('slug', 'bimtek-kepegawaian')->first();

        Livewire::test(ListPosts::class)
            ->callTableBulkAction('setCategory', $posts, data: ['category_id' => $category->id])
            ->assertNotified('2 artikel diperbarui')
            ->callTableBulkAction('publish', $posts)
            ->assertNotified('2 artikel diterbitkan');

        $posts->each(function (Post $post) use ($category) {
            $post->refresh();
            $this->assertSame($category->id, $post->category_id);
            $this->assertSame(PostStatus::Published, $post->status);
        });

        Livewire::test(ListPosts::class)->callTableBulkAction('unpublish', $posts);
        $posts->each(fn (Post $post) => $this->assertSame(PostStatus::Draft, $post->fresh()->status));
    }

    public function test_list_is_compact_and_sidebar_is_grouped(): void
    {
        Livewire::test(ListPosts::class)
            ->assertTableColumnDoesNotExist('image')
            ->assertTableColumnExists('category.name')
            ->assertTableColumnExists('status');

        $this->assertNull(RegistrationResource::getNavigationGroup());
        $this->assertSame('Konten', PostResource::getNavigationGroup());
        $this->assertSame('Materi', PackageResource::getNavigationGroup());
        $this->assertSame('Materi', TopicResource::getNavigationGroup());

        $this->get('/admin')->assertOk()->assertSeeInOrder(['Dasbor', 'Pendaftaran', 'Konten', 'Artikel', 'Jadwal', 'Materi', 'Pengaturan'], false);
    }

    public function test_row_actions_publish_and_view(): void
    {
        $post = $this->makePost();

        Livewire::test(ListPosts::class)
            ->assertTableActionExists('view')
            ->callTableAction('publish', $post);

        $this->assertSame(PostStatus::Published, $post->fresh()->status);
    }

    public function test_create_post_with_status_and_auto_slug(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm(['title' => 'Bimtek Penyusunan SKP 2026'])
            ->assertSchemaStateSet(['slug' => 'bimtek-penyusunan-skp-2026', 'status' => PostStatus::Draft])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::where('slug', 'bimtek-penyusunan-skp-2026')->sole();
        $this->assertNull($post->published_at);
        $this->assertSame(User::first()->id, $post->user_id);

        Livewire::test(CreatePost::class)
            ->fillForm(['title' => 'Artikel Langsung Terbit'])
            ->fillForm(['status' => 'published'])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame(PostStatus::Published, Post::where('title', 'Artikel Langsung Terbit')->sole()->status);
    }

    public function test_publish_date_field_is_hidden_for_drafts(): void
    {
        Livewire::test(CreatePost::class)
            ->assertFormFieldHidden('published_at')
            ->fillForm(['status' => 'published'])
            ->assertFormFieldVisible('published_at')
            ->fillForm(['status' => 'draft'])
            ->assertFormFieldHidden('published_at');
    }

    public function test_scheduled_post_requires_future_date(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm(['title' => 'Terjadwal', 'status' => 'scheduled'])
            ->fillForm(['published_at' => now()->subDay()->toDateTimeString()])
            ->call('create')
            ->assertHasFormErrors(['published_at']);

        Livewire::test(CreatePost::class)
            ->fillForm(['title' => 'Terjadwal OK', 'status' => 'scheduled'])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame(PostStatus::Scheduled, Post::where('title', 'Terjadwal OK')->sole()->status);
    }

    public function test_edit_form_hydrates_status_and_draft_clears_date(): void
    {
        $post = $this->makePost(['published_at' => now()->subDay()]);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSchemaStateSet(['status' => PostStatus::Published])
            ->assertActionExists('view')
            ->fillForm(['status' => 'draft'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($post->fresh()->published_at);
    }

    public function test_preview_link_for_drafts_is_signed(): void
    {
        $draft = $this->makePost(['title' => 'Draf Rahasia']);

        $this->get(route('posts.show', $draft))->assertNotFound();
        $this->get(route('posts.preview', ['post' => $draft->id]))->assertForbidden();
        $this->get($draft->publicUrl())
            ->assertOk()
            ->assertSee('Pratinjau')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $published = $this->makePost(['published_at' => now()->subHour()]);
        $this->assertSame(route('posts.show', $published), $published->publicUrl());
    }

    public function test_dashboard_widget_and_navigation_badge(): void
    {
        $this->makePost();
        $this->makePost(['published_at' => now()->subHour()]);

        Livewire::test(PostStats::class)->assertSee('Terbit Bulan Ini')->assertSee('Tanpa Kategori');
        $this->assertTrue(PostStats::canView());

        Registration::create(['schedule_id' => Schedule::first()->id, 'contact_name' => 'A', 'phone' => '0812', 'institution' => 'Dinas']);
        $this->assertSame('1', RegistrationResource::getNavigationBadge());

        $this->actingAs(User::factory()->role(UserRole::Cs)->create());
        $this->assertFalse(PostStats::canView());
    }

    public function test_panel_uses_site_identity_and_global_search(): void
    {
        Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'Pusat Edukasi Indonesia']);
        $this->makePost(['title' => 'Panduan SIASN Terbaru']);

        $this->get('/admin')->assertOk()->assertSee('Pusat Edukasi Indonesia');
        $this->get('/admin/posts')->assertOk()->assertSee('Tanpa Kategori')->assertSee('Tulis Artikel');

        $results = PostResource::getGlobalSearchResults('SIASN');
        $this->assertSame('Panduan SIASN Terbaru', (string) $results->first()->title);
    }
}
