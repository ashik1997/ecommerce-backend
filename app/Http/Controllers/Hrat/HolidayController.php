<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;
use App\Models\Hrat\Holiday;
use App\Services\Hrat\AttendanceSummaryService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::latest('holiday_date')->paginate(30);
        return view('backend.hrat.holidays.index', compact('holidays'));
    }

    public function store(Request $request, AttendanceSummaryService $summaryService)
    {
        $data = $this->validated($request);

        Holiday::create(array_merge($data, [
            'is_working_day_override' => $data['type'] === 'special_working_day' || $request->boolean('is_working_day_override'),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]));

        $this->regenerateAffectedDates([$data['holiday_date']], $summaryService);

        Toastr::success('Holiday saved.', 'Success');
        return back();
    }

    public function update(Request $request, Holiday $holiday, AttendanceSummaryService $summaryService)
    {
        $data = $this->validated($request, $holiday->id);
        $oldDate = $holiday->holiday_date?->toDateString();

        $holiday->update(array_merge($data, [
            'is_working_day_override' => $data['type'] === 'special_working_day' || $request->boolean('is_working_day_override'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => auth()->id(),
        ]));

        $this->regenerateAffectedDates([$oldDate, $data['holiday_date']], $summaryService);

        Toastr::success('Holiday updated.', 'Success');
        return back();
    }

    public function destroy(Holiday $holiday, AttendanceSummaryService $summaryService)
    {
        $date = $holiday->holiday_date?->toDateString();
        $holiday->delete();
        $this->regenerateAffectedDates([$date], $summaryService);

        Toastr::success('Holiday deleted.', 'Success');
        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('hrat_holidays', 'holiday_date')->ignore($ignoreId),
            ],
            'type' => ['required', 'in:public,company,special_working_day'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function regenerateAffectedDates(array $dates, AttendanceSummaryService $summaryService): void
    {
        collect($dates)
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->each(fn ($date) => $summaryService->generateForDate($date));
    }
}
