<?php

namespace App\Services\Hrat;

use App\Models\Hrat\DailyAttendanceSummary;
use App\Models\Hrat\EmployeeSalaryAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportService
{
    public function dailyQuery(Request $request)
    {
        return DailyAttendanceSummary::with('employee')
            ->when($request->date_from, fn ($q) => $q->whereDate('attendance_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('attendance_date', '<=', $request->date_to))
            ->when($request->employee_id, fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->late_only, fn ($q) => $q->where('is_late', true))
            ->when($request->absent_only, fn ($q) => $q->where('is_absent', true))
            ->when($request->overtime_only, fn ($q) => $q->where('overtime_minutes', '>', 0))
            ->latest('attendance_date');
    }

    public function monthlyRows(string $from, string $to)
    {
        $employees = User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();

        return $employees->map(function ($employee) use ($from, $to) {
            $query = DailyAttendanceSummary::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$from, $to]);
            $salary = EmployeeSalaryAssignment::with('salaryGrade')
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $to)
                ->where(function ($q) use ($from) {
                    $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from);
                })
                ->latest('effective_from')
                ->first();

            return [
                'employee' => $employee,
                'working_days' => (clone $query)->whereNotIn('status', ['holiday', 'holiday_present'])->count(),
                'present_days' => (clone $query)->where('is_present', true)->count(),
                'absent_days' => (clone $query)->where('is_absent', true)->count(),
                'late_count' => (clone $query)->where('is_late', true)->count(),
                'early_exit_count' => (clone $query)->where('is_early_exit', true)->count(),
                'incomplete_days' => (clone $query)->where('status', 'incomplete')->count(),
                'working_minutes' => (clone $query)->sum('net_working_minutes'),
                'overtime_minutes' => (clone $query)->sum('overtime_minutes'),
                'salary_grade' => $salary->salaryGrade->grade_name ?? null,
                'basic_salary' => $salary->basic_salary ?? 0,
            ];
        });
    }

    public function csvResponse($rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee Code', 'Employee Name', 'Date', 'First Entry', 'Last Exit', 'Entry Count', 'Exit Count', 'Working Minutes', 'Late Minutes', 'Early Exit Minutes', 'Overtime Minutes', 'Status', 'Source']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->employee_id,
                    $row->employee->name ?? 'N/A',
                    optional($row->attendance_date)->format('Y-m-d'),
                    $row->first_entry_time,
                    $row->last_exit_time,
                    $row->total_entry_count,
                    $row->total_exit_count,
                    $row->net_working_minutes,
                    $row->late_minutes,
                    $row->early_exit_minutes,
                    $row->overtime_minutes,
                    $row->status,
                    $row->source_summary,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
