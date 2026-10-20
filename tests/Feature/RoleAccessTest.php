<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function registration(): Registration
    {
        return Registration::create([
            'schedule_id' => Schedule::first()->id,
            'contact_name' => 'Budi',
            'phone' => '081234567890',
            'institution' => 'Dinas Contoh',
        ]);
    }

    public function test_seeded_admin_is_super_admin(): void
    {
        $this->assertSame(UserRole::SuperAdmin, User::first()->role);
    }

    public function test_all_roles_can_access_panel(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->role($role)->create();
            $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
            $this->actingAs($user)->get('/admin')->assertOk();
        }
    }

    public function test_cs_can_only_manage_registrations(): void
    {
        $cs = User::factory()->role(UserRole::Cs)->create();
        $registration = $this->registration();

        $this->actingAs($cs);
        $this->get('/admin/registrations')->assertOk();
        $this->get("/admin/registrations/{$registration->id}/edit")->assertOk();
        $this->get('/admin/registrations/create')->assertOk();

        foreach (['schedules', 'cities', 'venues', 'packages', 'categories', 'topics', 'posts', 'pages', 'users'] as $path) {
            $this->get("/admin/{$path}")->assertForbidden();
        }

        $this->assertTrue($cs->can('update', $registration));
        $this->assertFalse($cs->can('delete', $registration));
    }

    public function test_admin_manages_content_but_not_users(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin);
        $this->get('/admin/schedules')->assertOk();
        $this->get('/admin/posts')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->assertTrue($admin->can('delete', $this->registration()));
    }

    public function test_super_admin_manages_users(): void
    {
        $this->actingAs(User::first());
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/create')->assertOk();
        $this->get('/admin/users/'.User::first()->id.'/edit')->assertOk();
    }

    public function test_navigation_for_cs_hides_other_resources(): void
    {
        $cs = User::factory()->role(UserRole::Cs)->create();

        $this->actingAs($cs)->get('/admin')
            ->assertSee('/admin/registrations', false)
            ->assertDontSee('/admin/schedules"', false)
            ->assertDontSee('/admin/users"', false);
    }
}
