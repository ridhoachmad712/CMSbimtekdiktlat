<div>
    <form class="card grid grid-cols-2 gap-3 p-3 sm:gap-4 sm:p-4 lg:grid-cols-5" wire:submit.prevent role="search" aria-label="Filter jadwal">
        <div class="col-span-2 sm:col-span-1">
            <label for="filter-city" class="form-label">Kota</label>
            <select id="filter-city" wire:model.live="city" class="form-input">
                <option value="">Semua kota</option>
                @foreach ($cities as $c)
                    <option value="{{ $c->slug }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-month" class="form-label">Bulan</label>
            <select id="filter-month" wire:model.live="month" class="form-input">
                <option value="">Semua bulan</option>
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-year" class="form-label">Tahun</label>
            <select id="filter-year" wire:model.live="year" class="form-input">
                <option value="">Semua tahun</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-mode" class="form-label">Pelaksanaan</label>
            <select id="filter-mode" wire:model.live="mode" class="form-input">
                <option value="">Semua</option>
                @foreach ($modes as $m)
                    <option value="{{ $m->value }}">{{ $m->getLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="button" wire:click="resetFilters" class="btn btn-outline w-full">Reset filter</button>
        </div>
    </form>

    <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
        <p>{{ $schedules->total() }} jadwal ditemukan</p>
        <p wire:loading class="text-brand-700">Memuat…</p>
    </div>

    <div class="mt-3" wire:loading.class="opacity-60">
        @include('partials.schedule-table', ['schedules' => $schedules])
    </div>

    <div class="mt-6">{{ $schedules->links() }}</div>
</div>
