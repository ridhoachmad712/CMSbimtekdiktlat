{{-- Tabel jadwal. Variabel: $schedules (Collection|Paginator<Schedule>), $topic (opsional, untuk prefill daftar), $hideCity (opsional, di halaman kota) --}}
@php
    $topicSlug = isset($topic) ? $topic->slug : null;
    $hideCity ??= false;
@endphp

@if ($schedules->isEmpty())
    <div class="card p-8 text-center text-slate-500">
        Belum ada jadwal yang sesuai. Hubungi kami via WhatsApp untuk jadwal lain atau <em>In House Training</em>.
    </div>
@else
    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="hidden bg-slate-100 text-xs tracking-wide text-slate-600 uppercase sm:table-header-group">
                <tr>
                    <th class="px-4 py-3 font-semibold">Tanggal</th>
                    <th class="px-4 py-3 font-semibold">{{ $hideCity ? 'Venue / Materi' : 'Lokasi' }}</th>
                    <th class="px-4 py-3 font-semibold">Pelaksanaan</th>
                    <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($schedules as $schedule)
                    @php
                        $start = $schedule->start_date->locale('id');
                        $end = $schedule->end_date->locale('id');
                    @endphp
                    <tr class="grid grid-cols-[1fr_auto] items-center gap-x-3 gap-y-0.5 px-4 py-3 sm:table-row sm:px-0 sm:py-0" wire:key="schedule-{{ $schedule->id }}">
                        <td class="sm:px-4 sm:py-3">
                            <span class="font-semibold text-slate-900">
                                {{ $start->isSameMonth($end) ? $start->format('d') : $start->translatedFormat('d M') }} – {{ $end->translatedFormat('d F Y') }}
                            </span>
                            <span class="hidden text-xs text-slate-500 sm:block">{{ $start->translatedFormat('l') }} s.d. {{ $end->translatedFormat('l') }}</span>
                        </td>
                        <td class="sm:px-4 sm:py-3">
                            @unless ($hideCity)
                                <a href="{{ route('cities.show', $schedule->city) }}" class="font-medium text-brand-700 hover:underline">{{ $schedule->city->name }}</a>
                            @endunless
                            @if ($schedule->venue)
                                <span @class(['block text-xs text-slate-500', 'text-sm! font-medium text-slate-700!' => $hideCity])>{{ $schedule->venue->name }}</span>
                            @endif
                            @if ($schedule->topic)
                                <span class="block text-xs text-slate-600">Materi: {{ $schedule->topic->title }}</span>
                            @endif
                        </td>
                        <td class="sm:px-4 sm:py-3">
                            <span @class([
                                'inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-brand-50 text-brand-700' => $schedule->mode === \App\Enums\ScheduleMode::Offline,
                                'bg-sky-50 text-sky-700' => $schedule->mode === \App\Enums\ScheduleMode::Online,
                                'bg-amber-50 text-amber-800' => $schedule->mode === \App\Enums\ScheduleMode::Hybrid,
                            ])>{{ $schedule->mode->getLabel() }}</span>
                            @if ($schedule->status !== \App\Enums\ScheduleStatus::Open)
                                <span class="ml-1 inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $schedule->status->getLabel() }}</span>
                            @endif
                        </td>
                        <td class="col-start-2 row-span-3 row-start-1 self-center sm:px-4 sm:py-3 sm:text-right">
                            @if ($schedule->status === \App\Enums\ScheduleStatus::Open)
                                <a href="{{ route('register', array_filter(['jadwal' => $schedule->id, 'materi' => $topicSlug ?? $schedule->topic?->slug])) }}"
                                   class="btn btn-primary px-4">Daftar</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
