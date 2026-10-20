<?php

namespace App\Livewire;

use App\Enums\ScheduleMode;
use App\Enums\ScheduleStatus;
use App\Models\City;
use App\Models\Schedule;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ScheduleFilter extends Component
{
    use WithPagination;

    #[Url(as: 'kota', except: '')]
    public string $city = '';

    #[Url(as: 'bulan', except: '')]
    public string $month = '';

    #[Url(as: 'tahun', except: '')]
    public string $year = '';

    #[Url(except: '')]
    public string $mode = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('city', 'month', 'year', 'mode');
        $this->resetPage();
    }

    public function render(): View
    {
        $schedules = Schedule::query()
            ->with(['city', 'venue', 'topic'])
            ->whereDate('start_date', '>=', today())
            ->whereIn('status', [ScheduleStatus::Open, ScheduleStatus::Full])
            ->when($this->city, fn ($q) => $q->whereHas('city', fn ($c) => $c->where('slug', $this->city)))
            ->when($this->month, fn ($q) => $q->whereMonth('start_date', (int) $this->month))
            ->when($this->year, fn ($q) => $q->whereYear('start_date', (int) $this->year))
            ->when(ScheduleMode::tryFrom($this->mode), fn ($q, $mode) => $q->where('mode', $mode))
            ->orderBy('start_date')
            ->orderBy('city_id')
            ->paginate(20);

        return view('livewire.schedule-filter', [
            'schedules' => $schedules,
            'cities' => City::active()->get(['id', 'name', 'slug']),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => now()->locale('id')->month($m)->day(1)->translatedFormat('F')]),
            'years' => range(now()->year, now()->year + 2),
            'modes' => ScheduleMode::cases(),
        ]);
    }
}
