<?php

namespace App\Http\Controllers\Analytics\Actions;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Maps preset (or custom from/to) to current and previous analytics windows.
 * Previous window uses the same number of calendar days, ending the day before current starts.
 */
class ResolveAnalyticsWindowsAction
{
    public const MAX_RANGE_DAYS = 370;

    public const PRESETS = [
        'today',
        'yesterday',
        'week',
        'last_30_days',
        'this_month',
        'custom',
    ];

    /**
     * @param  array{preset?: string, from?: string|null, to?: string|null}  $input
     * @return array{
     *     current: array{start: Carbon, end: Carbon},
     *     previous: array{start: Carbon, end: Carbon},
     *     preset: string,
     *     preset_label: string
     * }
     */
    public function execute(array $input): array
    {
        $preset = $input['preset'] ?? 'this_month';
        if (! in_array($preset, self::PRESETS, true)) {
            $preset = 'this_month';
        }

        if ($preset === 'custom') {
            [$currentStart, $currentEnd] = $this->resolveCustomRange(
                $input['from'] ?? null,
                $input['to'] ?? null
            );
        } else {
            [$currentStart, $currentEnd] = $this->resolvePresetRange($preset);
        }

        [$previousStart, $previousEnd] = $this->previousEqualCalendarWindow($currentStart, $currentEnd);

        return [
            'current' => ['start' => $currentStart, 'end' => $currentEnd],
            'previous' => ['start' => $previousStart, 'end' => $previousEnd],
            'preset' => $preset,
            'preset_label' => $this->labelForPreset($preset),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolvePresetRange(string $preset): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'week' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'last_30_days' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'this_month' => [
                $now->copy()->startOfMonth()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            default => [
                $now->copy()->startOfMonth()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveCustomRange(?string $from, ?string $to): array
    {
        if (empty($from) || empty($to)) {
            throw new InvalidArgumentException('Custom range requires from and to dates.');
        }

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        if ($start->diffInDays($end) + 1 > self::MAX_RANGE_DAYS) {
            $end = $start->copy()->addDays(self::MAX_RANGE_DAYS - 1)->endOfDay();
        }

        return [$start, $end];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function previousEqualCalendarWindow(Carbon $currentStart, Carbon $currentEnd): array
    {
        $s = $currentStart->copy()->startOfDay();
        $e = $currentEnd->copy()->startOfDay();
        $days = (int) $s->diffInDays($e) + 1;

        $previousEnd = $s->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [$previousStart, $previousEnd];
    }

    protected function labelForPreset(string $preset): string
    {
        return match ($preset) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'This Week',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'custom' => 'Custom range',
            default => 'This Month',
        };
    }
}
