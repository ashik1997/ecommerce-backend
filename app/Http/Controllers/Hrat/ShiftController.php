<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\EmployeeSchedule;
use App\Models\Hrat\Shift;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::latest()->paginate(20);
        $dayOptions = $this->dayOptions();
        return view('backend.hrat.shifts.index', compact('shifts', 'dayOptions'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        [$workingDays, $weeklyHolidays] = $this->scheduleDays($request);
        $this->clearDefaultIfNeeded($request);

        Shift::create(array_merge($data, [
            'working_days' => $workingDays,
            'weekly_holidays' => $weeklyHolidays,
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]));

        Toastr::success('Shift saved.', 'Success');
        return back();
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $this->validated($request, $shift->id);
        [$workingDays, $weeklyHolidays] = $this->scheduleDays($request);
        $this->clearDefaultIfNeeded($request, $shift->id);

        $shift->update(array_merge($data, [
            'working_days' => $workingDays,
            'weekly_holidays' => $weeklyHolidays,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_default') ? true : $request->boolean('is_active'),
            'updated_by' => auth()->id(),
        ]));

        Toastr::success('Shift updated.', 'Success');
        return back();
    }

    public function destroy(Shift $shift)
    {
        if (EmployeeSchedule::where('shift_id', $shift->id)->exists()) {
            Toastr::error('This shift is assigned to employee schedules and cannot be deleted.', 'Error');
            return back();
        }

        $shift->delete();
        Toastr::success('Shift deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hrat_shifts', 'name')->ignore($ignoreId)],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('hrat_shifts', 'code')->ignore($ignoreId)],
            'office_entry_time' => ['required', 'date_format:H:i'],
            'entry_safe_time' => ['required', 'date_format:H:i', 'after_or_equal:office_entry_time'],
            'office_exit_time' => ['required', 'date_format:H:i'],
            'early_exit_safe_time' => ['required', 'date_format:H:i', 'before_or_equal:office_exit_time'],
            'overtime_after_minutes' => ['required', 'numeric', 'min:0'],
            'overtime_calculation_method' => ['required', 'in:from_exit_time,from_threshold_time'],
            'late_calculation_method' => ['required', 'in:from_entry_time,from_safe_time'],
            'entry_button_start_time' => ['required', 'date_format:H:i'],
            'entry_button_end_time' => ['required', 'date_format:H:i'],
            'exit_button_start_time' => ['required', 'date_format:H:i'],
            'exit_button_end_time' => ['required', 'date_format:H:i'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:0,6'],
            'weekly_holidays' => ['nullable', 'array'],
            'weekly_holidays.*' => ['integer', 'between:0,6'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function clearDefaultIfNeeded(Request $request, ?int $ignoreId = null): void
    {
        if (!$request->boolean('is_default')) {
            return;
        }

        Shift::when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->update(['is_default' => false]);
    }

    private function scheduleDays(Request $request): array
    {
        $workingDays = $this->normalizedDays($request->input('working_days'));
        $weeklyHolidays = $this->normalizedDays($request->input('weekly_holidays', []));

        if (array_intersect($workingDays, $weeklyHolidays)) {
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

    private function dayOptions(): array
    {
        return [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];
    }
}
