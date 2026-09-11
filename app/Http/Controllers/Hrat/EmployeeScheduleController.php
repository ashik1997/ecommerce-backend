<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\EmployeeSchedule;
use App\Models\Hrat\Shift;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeScheduleController extends Controller
{
    public function index()
    {
        $schedules = EmployeeSchedule::with(['employee', 'shift'])->latest()->paginate(20);
        $employees = $this->employees();
        $scheduleShiftIds = $schedules->getCollection()->pluck('shift_id')->filter()->all();
        $shifts = Shift::where('is_active', true)
            ->when($scheduleShiftIds, fn ($query) => $query->orWhereIn('id', $scheduleShiftIds))
            ->orderBy('name')
            ->get();
        $dayOptions = $this->dayOptions();
        return view('backend.hrat.schedules.index', compact('schedules', 'employees', 'shifts', 'dayOptions'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        [$workingDays, $weeklyHolidays] = $this->scheduleDays($request);
        $this->ensureNoOverlap($data);

        EmployeeSchedule::create(array_merge($data, [
            'working_days' => $workingDays,
            'weekly_holidays' => $weeklyHolidays,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]));
        Toastr::success('Employee schedule saved.', 'Success');
        return back();
    }

    public function update(Request $request, EmployeeSchedule $employeeSchedule)
    {
        $data = $this->validated($request);
        [$workingDays, $weeklyHolidays] = $this->scheduleDays($request);
        $isActive = $request->boolean('is_active');
        $this->ensureNoOverlap($data, $employeeSchedule->id, $isActive);

        $employeeSchedule->update(array_merge($data, [
            'working_days' => $workingDays,
            'weekly_holidays' => $weeklyHolidays,
            'is_active' => $isActive,
            'updated_by' => auth()->id(),
        ]));
        Toastr::success('Employee schedule updated.', 'Success');
        return back();
    }

    public function destroy(EmployeeSchedule $employeeSchedule)
    {
        $employeeSchedule->delete();
        Toastr::success('Employee schedule deleted.', 'Success');
        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
            'shift_id' => ['nullable', 'exists:hrat_shifts,id'],
            'office_entry_time' => ['nullable', 'date_format:H:i'],
            'entry_safe_time' => ['nullable', 'date_format:H:i'],
            'office_exit_time' => ['nullable', 'date_format:H:i'],
            'early_exit_safe_time' => ['nullable', 'date_format:H:i'],
            'overtime_after_minutes' => ['nullable', 'numeric', 'min:0'],
            'overtime_calculation_method' => ['nullable', 'in:from_exit_time,from_threshold_time'],
            'late_calculation_method' => ['nullable', 'in:from_entry_time,from_safe_time'],
            'entry_button_start_time' => ['nullable', 'date_format:H:i'],
            'entry_button_end_time' => ['nullable', 'date_format:H:i'],
            'exit_button_start_time' => ['nullable', 'date_format:H:i'],
            'exit_button_end_time' => ['nullable', 'date_format:H:i'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'between:0,6'],
            'weekly_holidays' => ['nullable', 'array'],
            'weekly_holidays.*' => ['integer', 'between:0,6'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);
    }

    private function employees()
    {
        return User::where('status', 1)->whereIn('user_type', [1, 2])->orderBy('name')->get();
    }

    private function scheduleDays(Request $request): array
    {
        $workingDays = $request->has('working_days') ? $this->normalizedDays($request->input('working_days')) : null;
        $weeklyHolidays = $request->has('weekly_holidays') ? $this->normalizedDays($request->input('weekly_holidays')) : null;

        if ($workingDays !== null && $weeklyHolidays !== null && array_intersect($workingDays, $weeklyHolidays)) {
            throw ValidationException::withMessages([
                'weekly_holidays' => 'A day cannot be both working day and weekly holiday.',
            ]);
        }

        return [$workingDays, $weeklyHolidays];
    }

    private function normalizedDays($days): array
    {
        return collect((array) $days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 0 && $day <= 6)
            ->unique()
            ->values()
            ->all();
    }

    private function ensureNoOverlap(array $data, ?int $ignoreId = null, bool $isActive = true): void
    {
        if (!$isActive) {
            return;
        }

        $from = $data['effective_from'];
        $to = $data['effective_to'] ?? '9999-12-31';
        $exists = EmployeeSchedule::where('employee_id', $data['employee_id'])
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('effective_from', '<=', $to)
            ->where(function ($query) use ($from) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'effective_from' => 'This employee already has an active schedule in the selected date range.',
            ]);
        }
    }

    private function dayOptions(): array
    {
        return [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];
    }
}
