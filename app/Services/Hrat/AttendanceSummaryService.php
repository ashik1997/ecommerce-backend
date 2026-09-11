<?php

namespace App\Services\Hrat;

use App\Models\Hrat\AttendanceLog;
use App\Models\Hrat\DailyAttendanceSummary;
use App\Models\Hrat\Holiday;
use App\Models\Hrat\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSummaryService
{
    public function __construct(private AttendanceConfigService $configService)
    {
    }

    public function generateForDate($date, ?int $employeeId = null): void
    {
        $date = Carbon::parse($date)->toDateString();
        $employees = $this->employeesQuery($employeeId)->get();

        foreach ($employees as $employee) {
            $this->generateForEmployeeDate($employee->id, $date);
        }
    }

    public function generateForRange($from, $to, ?int $employeeId = null): void
    {
        $cursor = Carbon::parse($from);
        $end = Carbon::parse($to);

        while ($cursor->lte($end)) {
            $this->generateForDate($cursor->toDateString(), $employeeId);
            $cursor->addDay();
        }
    }

    public function generateForEmployeeDate(int $employeeId, $date): DailyAttendanceSummary
    {
        $date = Carbon::parse($date)->toDateString();
        $schedule = $this->configService->scheduleFor($employeeId, $date);
        $logs = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('attendance_date', $date)
            ->orderBy('attendance_datetime')
            ->get();

        $entryLogs = $logs->where('punch_type', 'entry')->values();
        $exitLogs = $logs->where('punch_type', 'exit')->values();
        $firstEntry = optional($entryLogs->first())->attendance_time;
        $lastExit = optional($exitLogs->last())->attendance_time;
        $isWorkingDay = $this->isWorkingDay($schedule, $date);
        $holiday = $this->holidayFor($date);
        if ($holiday) {
            $isWorkingDay = (bool) $holiday->is_working_day_override;
        }
        $leave = $this->approvedLeaveFor($employeeId, $date);
        $isPresent = $logs->isNotEmpty();
        $grossMinutes = 0;
        $lateMinutes = 0;
        $earlyExitMinutes = 0;
        $overtimeMinutes = 0;
        $isLate = false;
        $isEarlyExit = false;
        $status = $isWorkingDay ? 'absent' : 'holiday';
        if (!$isPresent && $leave && $isWorkingDay) {
            $status = 'leave';
        }

        if ($firstEntry && $lastExit) {
            $entryAt = Carbon::parse($date . ' ' . $firstEntry);
            $exitAt = Carbon::parse($date . ' ' . $lastExit);
            if ($exitAt->gt($entryAt)) {
                $grossMinutes = $entryAt->diffInMinutes($exitAt);
            }

            if ($isWorkingDay) {
                [$isLate, $lateMinutes] = $this->lateResult($date, $firstEntry, $schedule);
                [$isEarlyExit, $earlyExitMinutes] = $this->earlyExitResult($date, $lastExit, $schedule);
                $overtimeMinutes = $this->overtimeMinutes($date, $lastExit, $schedule);
                $status = $this->statusFromFlags($isLate, $isEarlyExit);
            } else {
                $status = 'holiday_present';
            }
        } elseif ($isPresent) {
            $status = $isWorkingDay ? 'incomplete' : 'holiday_present';
        }

        return DailyAttendanceSummary::updateOrCreate(
            ['employee_id' => $employeeId, 'attendance_date' => $date],
            [
                'first_entry_time' => $firstEntry,
                'last_exit_time' => $lastExit,
                'total_entry_count' => $entryLogs->count(),
                'total_exit_count' => $exitLogs->count(),
                'gross_working_minutes' => $grossMinutes,
                'break_minutes' => 0,
                'net_working_minutes' => $grossMinutes,
                'is_present' => $isPresent,
                'is_absent' => !$isPresent && $isWorkingDay && !$leave,
                'is_late' => $isLate,
                'late_minutes' => $lateMinutes,
                'is_early_exit' => $isEarlyExit,
                'early_exit_minutes' => $earlyExitMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'status' => $status,
                'source_summary' => $logs->pluck('source')->unique()->implode(','),
                'schedule_snapshot' => $schedule,
                'remarks' => $this->remarks($leave, $holiday, $isWorkingDay),
                'generated_at' => now(),
            ]
        );
    }

    private function employeesQuery(?int $employeeId = null)
    {
        return User::where('status', 1)
            ->whereIn('user_type', [1, 2])
            ->when($employeeId, fn ($query) => $query->where('id', $employeeId));
    }

    private function isWorkingDay(array $schedule, string $date): bool
    {
        $day = Carbon::parse($date)->dayOfWeek;
        $holidays = $schedule['weekly_holidays'] ?? [6];
        $workingDays = $schedule['working_days'] ?? [0, 1, 2, 3, 4, 5];

        return in_array($day, $workingDays, true) && !in_array($day, $holidays, true);
    }

    private function holidayFor(string $date): ?Holiday
    {
        return Holiday::whereDate('holiday_date', $date)->where('is_active', true)->first();
    }

    private function approvedLeaveFor(int $employeeId, string $date): ?LeaveApplication
    {
        return LeaveApplication::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->first();
    }

    private function lateResult(string $date, string $firstEntry, array $schedule): array
    {
        $entryAt = Carbon::parse($date . ' ' . $firstEntry);
        $safeAt = Carbon::parse($date . ' ' . $schedule['entry_safe_time']);
        $baseAt = ($schedule['late_calculation_method'] ?? 'from_entry_time') === 'from_safe_time'
            ? $safeAt
            : Carbon::parse($date . ' ' . $schedule['office_entry_time']);

        if ($entryAt->gt($safeAt)) {
            return [true, max(0, $baseAt->diffInMinutes($entryAt, false))];
        }

        return [false, 0];
    }

    private function earlyExitResult(string $date, string $lastExit, array $schedule): array
    {
        $exitAt = Carbon::parse($date . ' ' . $lastExit);
        $safeAt = Carbon::parse($date . ' ' . $schedule['early_exit_safe_time']);
        $officeExitAt = Carbon::parse($date . ' ' . $schedule['office_exit_time']);

        if ($exitAt->lt($safeAt)) {
            return [true, max(0, $exitAt->diffInMinutes($officeExitAt, false))];
        }

        return [false, 0];
    }

    private function overtimeMinutes(string $date, string $lastExit, array $schedule): int
    {
        $exitAt = Carbon::parse($date . ' ' . $lastExit);
        $officeExitAt = Carbon::parse($date . ' ' . $schedule['office_exit_time']);
        $thresholdAt = $officeExitAt->copy()->addMinutes((int) ($schedule['overtime_after_minutes'] ?? 0));

        if ($exitAt->lte($thresholdAt)) {
            return 0;
        }

        $base = ($schedule['overtime_calculation_method'] ?? 'from_exit_time') === 'from_threshold_time'
            ? $thresholdAt
            : $officeExitAt;

        return max(0, $base->diffInMinutes($exitAt, false));
    }

    private function statusFromFlags(bool $isLate, bool $isEarlyExit): string
    {
        if ($isLate && $isEarlyExit) {
            return 'late_and_early_exit';
        }
        if ($isLate) {
            return 'late';
        }
        if ($isEarlyExit) {
            return 'early_exit';
        }

        return 'present';
    }

    private function remarks(?LeaveApplication $leave, ?Holiday $holiday, bool $isWorkingDay): ?string
    {
        if ($leave && $isWorkingDay) {
            return 'Approved leave: ' . ($leave->leaveType->name ?? 'Leave');
        }

        if ($holiday) {
            return $holiday->title;
        }

        return null;
    }
}
