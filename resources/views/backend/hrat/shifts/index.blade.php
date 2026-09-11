@extends('backend.master')
@section('page_title', 'Shifts')
@section('page_heading', 'Shifts')
@section('content')
    @php
        $defaultWorkingDays = [0, 1, 2, 3, 4, 5];
        $defaultWeeklyHolidays = [6];
        $createWorkingDays = collect(old('working_days', $defaultWorkingDays))->map(fn ($day) => (int) $day)->all();
        $createWeeklyHolidays = collect(old('weekly_holidays', $defaultWeeklyHolidays))->map(fn ($day) => (int) $day)->all();
    @endphp
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.shifts.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2"><label>Name <span class="text-danger">*</span></label><input name="name" class="form-control" placeholder="General Shift" required></div>
                <div class="col-md-2 mb-2"><label>Code</label><input name="code" class="form-control" placeholder="GEN"></div>
                @foreach ([
                    'office_entry_time' => 'Entry',
                    'entry_safe_time' => 'Safe Entry',
                    'office_exit_time' => 'Exit',
                    'early_exit_safe_time' => 'Safe Exit',
                    'entry_button_start_time' => 'Entry Btn Start',
                    'entry_button_end_time' => 'Entry Btn End',
                    'exit_button_start_time' => 'Exit Btn Start',
                    'exit_button_end_time' => 'Exit Btn End',
                ] as $field => $label)
                    <div class="col-md-2 mb-2"><label>{{ $label }}</label><input type="time" name="{{ $field }}" class="form-control" required></div>
                @endforeach
                <div class="col-md-2 mb-2"><label>OT After Min</label><input type="number" name="overtime_after_minutes" value="0" min="0" class="form-control" required></div>
                <div class="col-md-2 mb-2">
                    <label>OT Method</label>
                    <select name="overtime_calculation_method" class="form-control">
                        <option value="from_exit_time">From Exit</option>
                        <option value="from_threshold_time">From Threshold</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Late Method</label>
                    <select name="late_calculation_method" class="form-control">
                        <option value="from_entry_time">From Entry</option>
                        <option value="from_safe_time">From Safe</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2"><label>Description</label><input name="description" class="form-control" placeholder="Description"></div>
                <div class="col-md-4 mb-2">
                    <label class="d-block">Working Days <span class="text-danger">*</span></label>
                    @foreach ($dayOptions as $day => $label)
                        <label class="mr-2 mb-1"><input type="checkbox" name="working_days[]" value="{{ $day }}" {{ in_array($day, $createWorkingDays, true) ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                </div>
                <div class="col-md-4 mb-2">
                    <label class="d-block">Weekly Holidays</label>
                    @foreach ($dayOptions as $day => $label)
                        <label class="mr-2 mb-1"><input type="checkbox" name="weekly_holidays[]" value="{{ $day }}" {{ in_array($day, $createWeeklyHolidays, true) ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end"><label><input type="checkbox" name="is_default" value="1"> Default Shift</label></div>
                <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <tr><th>Name</th><th>Code</th><th>Entry</th><th>Safe</th><th>Exit</th><th>Safe Exit</th><th>OT</th><th>Working Days</th><th>Weekly Holidays</th><th>Default</th><th>Status</th><th>Action</th></tr>
                @foreach ($shifts as $shift)
                    @php
                        $shiftWorkingDays = collect($shift->working_days ?: $defaultWorkingDays)->map(fn ($day) => (int) $day)->all();
                        $shiftWeeklyHolidays = collect($shift->weekly_holidays ?: [])->map(fn ($day) => (int) $day)->all();
                    @endphp
                    <tr>
                        <form method="POST" action="{{ route('hrat.shifts.update', $shift) }}">
                            @csrf @method('PUT')
                            <td><input name="name" value="{{ $shift->name }}" class="form-control" required></td>
                            <td><input name="code" value="{{ $shift->code }}" class="form-control"></td>
                            <td><input type="time" name="office_entry_time" value="{{ substr($shift->office_entry_time, 0, 5) }}" class="form-control" required></td>
                            <td><input type="time" name="entry_safe_time" value="{{ substr($shift->entry_safe_time, 0, 5) }}" class="form-control" required></td>
                            <td><input type="time" name="office_exit_time" value="{{ substr($shift->office_exit_time, 0, 5) }}" class="form-control" required></td>
                            <td><input type="time" name="early_exit_safe_time" value="{{ substr($shift->early_exit_safe_time, 0, 5) }}" class="form-control" required></td>
                            <td><input type="number" name="overtime_after_minutes" value="{{ $shift->overtime_after_minutes }}" min="0" class="form-control" required></td>
                            <td style="min-width: 190px">
                                @foreach ($dayOptions as $day => $label)
                                    <label class="mr-1 mb-1"><input type="checkbox" name="working_days[]" value="{{ $day }}" {{ in_array($day, $shiftWorkingDays, true) ? 'checked' : '' }}> {{ $label }}</label>
                                @endforeach
                            </td>
                            <td style="min-width: 190px">
                                @foreach ($dayOptions as $day => $label)
                                    <label class="mr-1 mb-1"><input type="checkbox" name="weekly_holidays[]" value="{{ $day }}" {{ in_array($day, $shiftWeeklyHolidays, true) ? 'checked' : '' }}> {{ $label }}</label>
                                @endforeach
                            </td>
                            <td><label><input type="checkbox" name="is_default" value="1" {{ $shift->is_default ? 'checked' : '' }}> Default</label></td>
                            <td><label><input type="checkbox" name="is_active" value="1" {{ $shift->is_active ? 'checked' : '' }}> Active</label></td>
                            <td>
                                <input type="hidden" name="overtime_calculation_method" value="{{ $shift->overtime_calculation_method }}">
                                <input type="hidden" name="late_calculation_method" value="{{ $shift->late_calculation_method }}">
                                <input type="hidden" name="entry_button_start_time" value="{{ substr($shift->entry_button_start_time, 0, 5) }}">
                                <input type="hidden" name="entry_button_end_time" value="{{ substr($shift->entry_button_end_time, 0, 5) }}">
                                <input type="hidden" name="exit_button_start_time" value="{{ substr($shift->exit_button_start_time, 0, 5) }}">
                                <input type="hidden" name="exit_button_end_time" value="{{ substr($shift->exit_button_end_time, 0, 5) }}">
                                <input type="hidden" name="description" value="{{ $shift->description }}">
                                <button class="btn btn-sm btn-info mb-1">Update</button>
                        </form>
                                <form method="POST" action="{{ route('hrat.shifts.destroy', $shift) }}" onsubmit="return confirm('Delete this shift?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                    </tr>
                @endforeach
            </table>
        </div>
        {{ $shifts->links() }}
    </div></div>
@endsection
