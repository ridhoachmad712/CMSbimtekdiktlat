<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\RegistrationStats;
use App\Filament\Widgets\RegistrationStatusStats;
use App\Filament\Widgets\UpcomingSchedules;
use App\Models\City;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::first());
    }

    private function register(RegistrationStatus $status, ?string $createdAt = null): Registration
    {
        $registration = Registration::create([
            'schedule_id' => Schedule::first()->id,
            'contact_name' => 'Ani',
            'phone' => '081200000001',
            'institution' => 'Dinas',
            'status' => $status,
            'participants_count' => 2,
        ]);
        if ($createdAt) {
            $registration->forceFill(['created_at' => $createdAt])->save();
        }

        return $registration;
    }

    /** @return Collection<string, mixed> label => nilai */
    private function stats(string $widget)
    {
        return collect((fn () => $this->getStats())->call(new $widget))
            ->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);
    }

    public function test_registration_stats_count_today_and_month(): void
    {
        $this->travelTo('2026-10-20 10:00');
        $this->register(RegistrationStatus::New);
        $this->register(RegistrationStatus::Paid, '2026-10-05 09:00');
        $this->register(RegistrationStatus::Paid, '2026-09-30 09:00');

        $stats = $this->stats(RegistrationStats::class);

        $this->assertSame(1, $stats['Pendaftar Hari Ini']);
        $this->assertSame(2, $stats['Pendaftar Bulan Ini']);
        $this->assertSame(1, $stats['Perlu Dikonfirmasi']);

        Livewire::test(RegistrationStats::class)->assertSee('Pendaftar Hari Ini');
    }

    public function test_status_stats_show_each_status(): void
    {
        $this->register(RegistrationStatus::Paid);
        $this->register(RegistrationStatus::Paid);
        $this->register(RegistrationStatus::Cancelled);

        $stats = $this->stats(RegistrationStatusStats::class);

        $this->assertCount(count(RegistrationStatus::cases()), $stats);
        $this->assertSame(2, $stats['Lunas']);
        $this->assertSame(1, $stats['Batal']);
        $this->assertSame(0, $stats['Hadir']);
    }

    public function test_upcoming_schedules_lists_open_future_schedules(): void
    {
        $city = City::first();
        $soon = Schedule::create(['city_id' => $city->id, 'start_date' => today()->addDay(), 'end_date' => today()->addDays(4)]);
        $past = Schedule::create(['city_id' => $city->id, 'start_date' => today()->subMonth(), 'end_date' => today()->subMonth()->addDays(3)]);

        Livewire::test(UpcomingSchedules::class)
            ->assertCanSeeTableRecords([$soon])
            ->assertCanNotSeeTableRecords([$past]);
    }

    public function test_dashboard_renders_widgets_for_cs(): void
    {
        $this->actingAs(User::factory()->role(UserRole::Cs)->create())
            ->get('/admin')
            ->assertOk()
            ->assertSeeLivewire(RegistrationStats::class)
            ->assertSeeLivewire(RegistrationStatusStats::class)
            ->assertSeeLivewire(UpcomingSchedules::class);
    }
}
