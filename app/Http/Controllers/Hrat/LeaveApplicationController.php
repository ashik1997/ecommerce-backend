<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\Holiday;
use App\Models\Hrat\LeaveApplication;
use App\Models\Hrat\LeaveBalance;
use App\Models\Hrat\LeaveType;
use App\Models\User;
use App\Services\Hrat\AttendanceConfigService;
use App\Services\Hrat\AttendanceSummaryService;
use App\Services\Hrat\LeaveBalanceService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveApplicationController extends Controller
{
    public function __construct(private AttendanceConfigService $configService)
    {
    }

    public function index()
    {
        $applications = LeaveApplication::with(['employee', 'leaveType', 'approver', 'rejecter'])->latest()->paginate(30);
        $employees = User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();
        $balances = LeaveBalance::with(['employee', 'leaveType'])->where('year', now()->year)->latest()->limit(30)->get();

        return view('backend.hrat.leave_applications.index', compact('applications', 'employees', 'leaveTypes', 'balances'));
    }

    public function store(Request $request, LeaveBalanceService $balanceService)
    {
        $data = $this->validated($request);
        $this->ensureSameBalanceYear($data['from_date'], $data['to_date']);
        $this->ensureNoOverlap($data);
        $totalDays = $this->totalDays((int) $data['employee_id'], $data['from_date'], $data['to_date']);
        $year = (int) Carbon::parse($data['from_date'])->format('Y');

        if ($totalDays <= 0) {
            Toastr::error('Selected leave range has no working days.', 'Error');
            return back()->withInput();
        }

        if (!$balanceService->canApply((int) $data['employee_id'], (int) $data['leave_type_id'], $year, $totalDays)) {
            Toastr::error('Insufficient leave balance for this application.', 'Error');
            return back()->withInput();
        }

        LeaveApplication::create(array_merge($data, [
            'total_days' => $totalDays,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]));

        Toastr::success('Leave application created.', 'Success');
        return back();
    }

    public function update(Request $request, LeaveApplication $leaveApplication, LeaveBalanceService $balanceService)
    {
        if ($leaveApplication->status !== 'pending') {
            Toastr::warning('Only pending leave applications can be updated.', 'Warning');
            return back();
        }

        $data = $this->validated($request);
        $this->ensureSameBalanceYear($data['from_date'], $data['to_date']);
        $this->ensureNoOverlap($data, $leaveApplication->id);
        $totalDays = $this->totalDays((int) $data['employee_id'], $data['from_date'], $data['to_date']);
        $year = (int) Carbon::parse($data['from_date'])->format('Y');

        if ($totalDays <= 0) {
            Toastr::error('Selected leave range has no working days.', 'Error');
            return back()->withInput();
        }

        if (!$balanceService->canApply((int) $data['employee_id'], (int) $data['leave_type_id'], $year, $totalDays)) {
            Toastr::error('Insufficient leave balance for this application.', 'Error');
            return back()->withInput();
        }

        $leaveApplication->update(array_merge($data, [
            'total_days' => $totalDays,
        ]));

        Toastr::success('Leave application updated.', 'Success');
        return back();
    }

    public function destroy(LeaveApplication $leaveApplication)
    {
        if ($leaveApplication->status === 'approved') {
            Toastr::warning('Approved leave cannot be deleted.', 'Warning');
            return back();
        }

        $leaveApplication->delete();
        Toastr::success('Leave application deleted.', 'Success');
        return back();
    }

    public function approve(LeaveApplication $leaveApplication, AttendanceSummaryService $summaryService, LeaveBalanceService $balanceService)
    {
        if ($leaveApplication->status !== 'pending') {
            Toastr::warning('This leave application is already reviewed.', 'Warning');
            return back();
        }

        $this->ensureNoOverlap($leaveApplication->only(['employee_id', 'from_date', 'to_date']), $leaveApplication->id);
        if (!$balanceService->canApply(
            (int) $leaveApplication->employee_id,
            (int) $leaveApplication->leave_type_id,
            (int) $leaveApplication->from_date->format('Y'),
            (float) $leaveApplication->total_days
        )) {
            Toastr::error('Insufficient leave balance for this application.', 'Error');
            return back();
        }

        DB::transaction(function () use ($leaveApplication, $summaryService, $balanceService) {
            $leaveApplication->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $balanceService->consume($leaveApplication);
            $this->regenerateRange($leaveApplication, $summaryService);
        });

        Toastr::success('Leave application approved.', 'Success');
        return back();
    }

    public function reject(Request $request, LeaveApplication $leaveApplication)
    {
        if ($leaveApplication->status !== 'pending') {
            Toastr::warning('This leave application is already reviewed.', 'Warning');
            return back();
        }

        $data = $request->validate(['review_note' => ['nullable', 'string']]);
        $leaveApplication->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        Toastr::success('Leave application rejected.', 'Success');
        return back();
    }

    public function cancel(Request $request, LeaveApplication $leaveApplication, AttendanceSummaryService $summaryService, LeaveBalanceService $balanceService)
    {
        if ($leaveApplication->status !== 'approved') {
            Toastr::warning('Only approved leave applications can be cancelled.', 'Warning');
            return back();
        }

        $data = $request->validate(['review_note' => ['nullable', 'string']]);

        DB::transaction(function () use ($leaveApplication, $summaryService, $balanceService, $data) {
            $balanceService->release($leaveApplication);
            $leaveApplication->update([
                'status' => 'cancelled',
                'review_note' => $data['review_note'] ?? 'Cancelled from leave list',
            ]);
            $this->regenerateRange($leaveApplication, $summaryService);
        });

        Toastr::success('Leave application cancelled and balance restored.', 'Success');
        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
            'leave_type_id' => ['required', 'exists:hrat_leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string'],
        ]);
    }

    private function totalDays(int $employeeId, string $from, string $to): int
    {
        $days = 0;
        $cursor = Carbon::parse($from);
        $end = Carbon::parse($to);

        while ($cursor->lte($end)) {
            if ($this->isLeaveWorkingDay($employeeId, $cursor->toDateString())) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function ensureSameBalanceYear(string $from, string $to): void
    {
        if (Carbon::parse($from)->year !== Carbon::parse($to)->year) {
            throw ValidationException::withMessages([
                'to_date' => 'Leave applications cannot cross balance years.',
            ]);
        }
    }

    private function ensureNoOverlap(array $data, ?int $ignoreId = null): void
    {
        $from = Carbon::parse($data['from_date'])->toDateString();
        $to = Carbon::parse($data['to_date'])->toDateString();
        $exists = LeaveApplication::where('employee_id', $data['employee_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('from_date', '<=', $to)
            ->whereDate('to_date', '>=', $from)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'from_date' => 'This employee already has a pending or approved leave in the selected date range.',
            ]);
        }
    }

    private function isLeaveWorkingDay(int $employeeId, string $date): bool
    {
        $schedule = $this->configService->scheduleFor($employeeId, $date);
        $day = Carbon::parse($date)->dayOfWeek;
        $workingDays = $schedule['working_days'] ?? [0, 1, 2, 3, 4, 5];
        $weeklyHolidays = $schedule['weekly_holidays'] ?? [6];

        $isWorkingDay = in_array($day, $workingDays, true) && !in_array($day, $weeklyHolidays, true);
        $holiday = Holiday::whereDate('holiday_date', $date)->where('is_active', true)->first();
        if ($holiday) {
            return (bool) $holiday->is_working_day_override;
        }

        return $isWorkingDay;
    }

    private function regenerateRange(LeaveApplication $leaveApplication, AttendanceSummaryService $summaryService): void
    {
        $cursor = $leaveApplication->from_date->copy();
        while ($cursor->lte($leaveApplication->to_date)) {
            $summaryService->generateForEmployeeDate($leaveApplication->employee_id, $cursor->toDateString());
            $cursor->addDay();
        }
    }
}
