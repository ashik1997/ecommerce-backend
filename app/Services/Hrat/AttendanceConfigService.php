<?php

namespace App\Services\Hrat;

use App\Models\Hrat\AttendanceConfig;
use App\Models\Hrat\EmployeeSchedule;
use App\Models\Hrat\Shift;
use Carbon\Carbon;

class AttendanceConfigService
{
    public function activeConfig(): AttendanceConfig
    {
        return AttendanceConfig::where('is_active', true)->latest('id')->first()
            ?: AttendanceConfig::create([
                'office_entry_time' => '10:00:00',
                'entry_safe_time' => '10:15:00',
                'office_exit_time' => '19:00:00',
                'early_exit_safe_time' => '18:55:00',
                'entry_button_start_time' => '09:45:00',
                'entry_button_end_time' => '10:15:00',
                'exit_button_start_time' => '18:50:00',
                'exit_button_end_time' => '19:30:00',
                'working_days' => [0, 1, 2, 3, 4, 5],
                'weekly_holidays' => [6],
                'created_by' => auth()->id(),
            ]);
    }

    public function scheduleFor(int $employeeId, $date): array
    {
        $date = Carbon::parse($date)->toDateString();
        $config = $this->activeConfig();

        $schedule = EmployeeSchedule::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->latest('effective_from')
            ->first();

        $base = $config->toArray();
        $shift = $schedule?->shift ?: Shift::where('is_default', true)->where('is_active', true)->latest('id')->first();
        if ($shift) {
            foreach ($shift->toArray() as $key => $value) {
                if ($value !== null && !in_array($key, ['id', 'name', 'code', 'description', 'created_at', 'updated_at'], true)) {
                    $base[$key] = $value;
                }
            }
            $base['shift_id'] = $shift->id;
            $base['shift_name'] = $shift->name;
        }

        if (!$schedule) {
            return $base;
        }

        foreach ($schedule->toArray() as $key => $value) {
            if ($value !== null && !in_array($key, ['id', 'shift', 'created_at', 'updated_at'], true)) {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    public function attendanceMonthRange(int $year, int $month): array
    {
        $config = $this->activeConfig();
        $startDay = max(1, min(31, (int) $config->month_start_day));
        $endDay = $config->month_end_day ? max(1, min(31, (int) $config->month_end_day)) : null;

        $start = Carbon::create($year, $month, 1)->day(min($startDay, Carbon::create($year, $month, 1)->daysInMonth));
        if (!$endDay || $startDay === 1) {
            $end = $start->copy()->endOfMonth();
        } elseif ($endDay >= $startDay) {
            $end = $start->copy()->day(min($endDay, $start->daysInMonth));
        } else {
            $end = $start->copy()->addMonth()->day(min($endDay, $start->copy()->addMonth()->daysInMonth));
        }

        return [$start->toDateString(), $end->toDateString()];
    }
}
