<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\Branch;
use App\Models\Hrat\DailyAttendanceSummary;
use App\Models\Hrat\Department;
use App\Models\Hrat\EmployeeProfile;
use App\Models\Hrat\LeaveApplication;
use App\Models\Hrat\LeaveType;
use App\Models\Hrat\PayrollLine;
use App\Models\User;
use App\Services\Hrat\AttendanceConfigService;
use App\Services\Hrat\AttendanceReportService;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function daily(Request $request, AttendanceReportService $service)
    {
        $employees = $this->employees();
        $rows = $service->dailyQuery($request)->paginate(30)->appends($request->query());
        return view('backend.hrat.reports.daily', compact('rows', 'employees'));
    }

    public function monthly(Request $request, AttendanceConfigService $configService, AttendanceReportService $service)
    {
        $year = (int) ($request->year ?: now()->year);
        $month = (int) ($request->month ?: now()->month);
        [$from, $to] = $configService->attendanceMonthRange($year, $month);
        $rows = $service->monthlyRows($from, $to);
        return view('backend.hrat.reports.monthly', compact('rows', 'from', 'to', 'year', 'month'));
    }

    public function absent(Request $request, AttendanceReportService $service)
    {
        $request->merge(['absent_only' => true]);
        return $this->daily($request, $service);
    }

    public function late(Request $request, AttendanceReportService $service)
    {
        $request->merge(['late_only' => true]);
        return $this->daily($request, $service);
    }

    public function overtime(Request $request, AttendanceReportService $service)
    {
        $request->merge(['overtime_only' => true]);
        return $this->daily($request, $service);
    }

    public function exportCsv(Request $request, AttendanceReportService $service)
    {
        $rows = $service->dailyQuery($request)->get();
        return $service->csvResponse($rows, 'hrat_attendance_report.csv');
    }

    public function leave(Request $request)
    {
        $employees = $this->employees();
        $leaveTypes = LeaveType::orderBy('name')->get();
        $applications = LeaveApplication::with(['employee', 'leaveType', 'approver', 'rejecter'])
            ->when($request->date_from, fn ($query) => $query->whereDate('to_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($query) => $query->whereDate('from_date', '<=', $request->date_to))
            ->when($request->employee_id, fn ($query) => $query->where('employee_id', $request->employee_id))
            ->when($request->leave_type_id, fn ($query) => $query->where('leave_type_id', $request->leave_type_id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(30)
            ->appends($request->query());

        return view('backend.hrat.reports.leave', compact('applications', 'employees', 'leaveTypes'));
    }

    public function employeeMaster(Request $request)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $profiles = EmployeeProfile::with(['user', 'departmentInfo', 'designationInfo', 'branchInfo', 'reportingManager'])
            ->when($request->department_id, fn ($query) => $query->where('department_id', $request->department_id))
            ->when($request->branch_id, fn ($query) => $query->where('branch_id', $request->branch_id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(30)
            ->appends($request->query());

        return view('backend.hrat.reports.employee-master', compact('profiles', 'departments', 'branches'));
    }

    public function departmentBranch(Request $request)
    {
        $from = $request->date_from ?: now()->startOfMonth()->toDateString();
        $to = $request->date_to ?: now()->endOfMonth()->toDateString();
        $groupBy = in_array($request->group_by, ['department', 'branch'], true) ? $request->group_by : 'department';
        $rows = $this->departmentBranchRows($from, $to, $groupBy);

        return view('backend.hrat.reports.department-branch', compact('rows', 'from', 'to', 'groupBy'));
    }

    private function employees()
    {
        return User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();
    }

    private function departmentBranchRows(string $from, string $to, string $groupBy)
    {
        $profiles = EmployeeProfile::with(['departmentInfo', 'branchInfo'])
            ->whereNotNull('user_id')
            ->get();

        return $profiles
            ->groupBy(fn ($profile) => $this->profileGroupName($profile, $groupBy))
            ->map(function ($groupProfiles, $groupName) use ($from, $to) {
                $employeeIds = $groupProfiles->pluck('user_id')->filter()->values();
                $summaries = DailyAttendanceSummary::whereIn('employee_id', $employeeIds)
                    ->whereBetween('attendance_date', [$from, $to]);
                $payroll = PayrollLine::whereIn('employee_id', $employeeIds)
                    ->whereHas('payroll', function ($query) use ($from, $to) {
                        $query->whereDate('from_date', '<=', $to)
                            ->whereDate('to_date', '>=', $from);
                    });

                return [
                    'group' => $groupName,
                    'employees' => $employeeIds->count(),
                    'present_days' => (clone $summaries)->where('is_present', true)->count(),
                    'absent_days' => (clone $summaries)->where('is_absent', true)->count(),
                    'late_days' => (clone $summaries)->where('is_late', true)->count(),
                    'leave_days' => (clone $summaries)->where('status', 'leave')->count(),
                    'overtime_minutes' => (clone $summaries)->sum('overtime_minutes'),
                    'net_payable' => (clone $payroll)->sum('net_payable'),
                ];
            })
            ->sortBy('group')
            ->values();
    }

    private function profileGroupName(EmployeeProfile $profile, string $groupBy): string
    {
        if ($groupBy === 'branch') {
            return $profile->branchInfo->name ?? $profile->branch ?? 'Unassigned';
        }

        return $profile->departmentInfo->name ?? $profile->department ?? 'Unassigned';
    }
}
