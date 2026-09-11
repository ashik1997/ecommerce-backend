<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrat\AttendanceLog;
use App\Models\Hrat\DailyAttendanceSummary;
use App\Services\Hrat\AttendanceConfigService;
use App\Services\Hrat\AttendanceLogService;
use App\Services\Hrat\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function todayStatus(Request $request, AttendanceConfigService $configService)
    {
        $employee = $request->user();
        $date = now()->toDateString();
        $schedule = $configService->scheduleFor($employee->id, $date);
        $summary = DailyAttendanceSummary::where('employee_id', $employee->id)->whereDate('attendance_date', $date)->first();

        return response()->json([
            'date' => $date,
            'employee_id' => $employee->id,
            'entry_button_visible' => $this->buttonVisible($schedule['entry_button_start_time'], $schedule['entry_button_end_time']),
            'exit_button_visible' => $this->buttonVisible($schedule['exit_button_start_time'], $schedule['exit_button_end_time']),
            'entry_button_time_range' => ['start' => $schedule['entry_button_start_time'], 'end' => $schedule['entry_button_end_time']],
            'exit_button_time_range' => ['start' => $schedule['exit_button_start_time'], 'end' => $schedule['exit_button_end_time']],
            'first_entry_time' => $summary->first_entry_time ?? null,
            'last_exit_time' => $summary->last_exit_time ?? null,
            'status' => $summary->status ?? 'not_submitted',
            'message' => $summary?->first_entry_time ? 'Entry already submitted' : 'Attendance is open',
        ]);
    }

    public function entry(Request $request, AttendanceConfigService $configService, AttendanceLogService $logService)
    {
        return $this->submitPunch($request, $configService, $logService, 'entry');
    }

    public function exit(Request $request, AttendanceConfigService $configService, AttendanceLogService $logService)
    {
        return $this->submitPunch($request, $configService, $logService, 'exit');
    }

    public function monthlySummary(Request $request, AttendanceConfigService $configService, AttendanceReportService $reportService)
    {
        [$from, $to] = $configService->attendanceMonthRange((int) ($request->year ?: now()->year), (int) ($request->month ?: now()->month));
        $rows = DailyAttendanceSummary::where('employee_id', $request->user()->id)->whereBetween('attendance_date', [$from, $to]);

        return response()->json([
            'from' => $from,
            'to' => $to,
            'present_days' => (clone $rows)->where('is_present', true)->count(),
            'absent_days' => (clone $rows)->where('is_absent', true)->count(),
            'late_count' => (clone $rows)->where('is_late', true)->count(),
            'overtime_minutes' => (clone $rows)->sum('overtime_minutes'),
        ]);
    }

    public function dateRangeReport(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return response()->json([
            'items' => DailyAttendanceSummary::where('employee_id', $request->user()->id)
                ->whereBetween('attendance_date', [$data['from'], $data['to']])
                ->orderBy('attendance_date')
                ->get(),
        ]);
    }

    private function submitPunch(Request $request, AttendanceConfigService $configService, AttendanceLogService $logService, string $type)
    {
        $employee = $request->user();
        $date = now()->toDateString();
        $schedule = $configService->scheduleFor($employee->id, $date);
        $start = $type === 'entry' ? $schedule['entry_button_start_time'] : $schedule['exit_button_start_time'];
        $end = $type === 'entry' ? $schedule['entry_button_end_time'] : $schedule['exit_button_end_time'];

        if (!$this->buttonVisible($start, $end)) {
            return response()->json(['success' => false, 'message' => ucfirst($type) . ' button is not visible now.'], 422);
        }

        if ($type === 'entry' && empty($schedule['allow_duplicate_employee_panel_entry'])) {
            $exists = AttendanceLog::where('employee_id', $employee->id)->whereDate('attendance_date', $date)->where('punch_type', 'entry')->exists();
            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Entry already submitted.'], 422);
            }
        }

        if ($type === 'exit' && empty($schedule['allow_exit_without_entry'])) {
            $hasEntry = AttendanceLog::where('employee_id', $employee->id)->whereDate('attendance_date', $date)->where('punch_type', 'entry')->exists();
            if (!$hasEntry) {
                return response()->json(['success' => false, 'message' => 'Entry is required before exit.'], 422);
            }
        }

        $log = $logService->create([
            'employee_id' => $employee->id,
            'employee_code' => (string) $employee->id,
            'attendance_date' => $date,
            'attendance_time' => now()->format('H:i'),
            'punch_type' => $type,
            'source' => 'employee_panel',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'created_by' => $employee->id,
        ], false);

        return response()->json(['success' => true, 'message' => ucfirst($type) . ' submitted.', 'data' => $log]);
    }

    private function buttonVisible($start, $end): bool
    {
        $now = Carbon::parse(now()->format('H:i:s'));
        return $now->betweenIncluded(Carbon::parse($start), Carbon::parse($end));
    }
}
