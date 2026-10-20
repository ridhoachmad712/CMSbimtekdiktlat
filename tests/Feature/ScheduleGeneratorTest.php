<?php

namespace Tests\Feature;

use App\Enums\ScheduleMode;
use App\Enums\UserRole;
use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Models\City;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_generates_recurring_schedules_per_city(): void
    {
        $cities = City::take(2)->pluck('id')->all();
        $before = Schedule::count();

        $result = app(ScheduleGenerator::class)->generate(
            cityIds: $cities,
            startDate: '2030-01-07',
            intervalWeeks: 2,
            durationDays: 4,
            occurrences: 3,
            mode: ScheduleMode::Online,
        );

        $this->assertSame(['created' => 6, 'skipped' => 0], $result);
        $this->assertSame($before + 6, Schedule::count());

        $dates = Schedule::where('city_id', $cities[0])->whereYear('start_date', 2030)->orderBy('start_date')->get();
        $this->assertSame(['2030-01-07', '2030-01-21', '2030-02-04'], $dates->map(fn ($s) => $s->start_date->toDateString())->all());
        $this->assertSame('2030-01-10', $dates->first()->end_date->toDateString());
        $this->assertSame(ScheduleMode::Online, $dates->first()->mode);
    }

    public function test_skips_duplicate_city_and_date(): void
    {
        $city = City::first();
        Schedule::create(['city_id' => $city->id, 'start_date' => '2030-01-14', 'end_date' => '2030-01-17']);

        $result = app(ScheduleGenerator::class)->generate([$city->id], '2030-01-07', 1, 4, 3);

        $this->assertSame(['created' => 2, 'skipped' => 1], $result);
        $this->assertSame(1, Schedule::where('city_id', $city->id)->whereDate('start_date', '2030-01-14')->count());

        // Menjalankan ulang tidak membuat duplikat.
        $this->assertSame(['created' => 0, 'skipped' => 3], app(ScheduleGenerator::class)->generate([$city->id], '2030-01-07', 1, 4, 3));
    }

    public function test_filament_action_generates_schedules(): void
    {
        $this->actingAs(User::first());
        $cities = City::take(3)->pluck('id')->all();

        Livewire::test(ListSchedules::class)
            ->callAction('generate', data: [
                'city_ids' => $cities,
                'start_date' => '2031-03-03',
                'interval_weeks' => 1,
                'duration_days' => 3,
                'occurrences' => 2,
                'mode' => ScheduleMode::Offline->value,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('6 jadwal dibuat');

        $this->assertSame(6, Schedule::whereYear('start_date', 2031)->count());
        $this->assertSame('2031-03-05', Schedule::whereYear('start_date', 2031)->orderBy('start_date')->first()->end_date->toDateString());
    }

    public function test_cs_cannot_use_generator(): void
    {
        $cs = User::factory()->role(UserRole::Cs)->create();

        $this->assertFalse($cs->can('create', Schedule::class));
    }
}
