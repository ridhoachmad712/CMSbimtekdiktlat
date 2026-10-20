<?php

namespace App\Services;

use App\Enums\ScheduleMode;
use App\Enums\ScheduleStatus;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Membuat jadwal berulang untuk banyak kota sekaligus.
 * Jadwal dengan kota + tanggal mulai yang sama dilewati (tidak diduplikasi).
 */
class ScheduleGenerator
{
    /**
     * @param  array<int|string>  $cityIds
     * @return array{created: int, skipped: int}
     */
    public function generate(
        array $cityIds,
        string $startDate,
        int $intervalWeeks,
        int $durationDays,
        int $occurrences,
        ScheduleMode|string $mode = ScheduleMode::Offline,
        int|string|null $topicId = null,
        ?int $quota = null,
    ): array {
        $first = CarbonImmutable::parse($startDate)->startOfDay();
        $dates = collect(range(0, max($occurrences, 1) - 1))
            ->map(fn (int $i) => $first->addWeeks($i * max($intervalWeeks, 1)));

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($cityIds, $dates, $durationDays, $mode, $topicId, $quota, &$created, &$skipped) {
            $existing = Schedule::query()
                ->whereIn('city_id', $cityIds)
                ->whereDate('start_date', '>=', $dates->first()->toDateString())
                ->whereDate('start_date', '<=', $dates->last()->toDateString())
                ->get(['city_id', 'start_date'])
                ->map(fn (Schedule $s) => $s->city_id.'|'.$s->start_date->toDateString())
                ->flip();

            foreach (array_unique($cityIds) as $cityId) {
                foreach ($dates as $date) {
                    if ($existing->has($cityId.'|'.$date->toDateString())) {
                        $skipped++;

                        continue;
                    }

                    Schedule::create([
                        'city_id' => $cityId,
                        'topic_id' => blank($topicId) ? null : $topicId,
                        'start_date' => $date->toDateString(),
                        'end_date' => $date->addDays(max($durationDays, 1) - 1)->toDateString(),
                        'mode' => $mode,
                        'quota' => $quota,
                        'status' => ScheduleStatus::Open,
                    ]);
                    $created++;
                }
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }
}
