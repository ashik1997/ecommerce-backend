@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Employee Attendance Schedules')
@section('page_heading', 'Employee Attendance Schedules')
@section('content')
    @php
        $createWorkingDays = collect(old('working_days', []))->map(fn ($day) => (int) $day)->all();
        $createWeeklyHolidays = collect(old('weekly_holidays', []))->map(fn ($day) => (int) $day)->all();
    @endphp
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.employee-schedules.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    @include('backend.hrat.partials.employee-select', ['employees' => $employees, 'required' => true])
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Shift</label>
                    <select name="shift_id" class="form-control">
                        <option value="">Use default config</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Office Entry Time</label>
                    <input type="time" name="office_entry_time" class="form-control" placeholder="Entry">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Entry Safe Time</label>
                    <input type="time" name="entry_safe_time" class="form-control" placeholder="Safe">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Office Exit Time</label>
                    <input type="time" name="office_exit_time" class="form-control" placeholder="Exit">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Early Exit Safe Time</label>
                    <input type="time" name="early_exit_safe_time" class="form-control" placeholder="Early safe">
                </div>
                <div class="col-md-1 mb-2">
                    <label class="form-label">OT Min</label>
                    <input type="number" name="overtime_after_minutes" class="form-control" placeholder="OT">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="form-control" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to" class="form-control">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">OT Method</label>
                    <select name="overtime_calculation_method" class="form-control"><option value="">OT Method</option><option value="from_exit_time">From exit</option><option value="from_threshold_time">From threshold</option></select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Late Method</label>
                    <select name="late_calculation_method" class="form-control"><option value="">Late Method</option><option value="from_entry_time">From entry</option><option value="from_safe_time">From safe</option></select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="d-block">Working Days Override</label>
                    @foreach ($dayOptions as $day => $label)
                        <label class="mr-2 mb-1"><input type="checkbox" name="working_days[]" value="{{ $day }}" {{ in_array($day, $createWorkingDays, true) ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                    <small class="form-text text-muted">Leave blank to inherit from selected shift/default config.</small>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="d-block">Weekly Holidays Override</label>
                    @foreach ($dayOptions as $day => $label)
                        <label class="mr-2 mb-1"><input type="checkbox" name="weekly_holidays[]" value="{{ $day }}" {{ in_array($day, $createWeeklyHolidays, true) ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                    <small class="form-text text-muted">Leave blank to inherit from selected shift/default config.</small>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-success">Save Schedule</button></div>
            </div>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <tr><th>Employee</th><th>Shift</th><th>Entry</th><th>Safe</th><th>Exit</th><th>Early Safe</th><th>OT</th><th>Methods</th><th>Effective</th><th>Working</th><th>Holidays</th><th>Status</th><th>Action</th></tr>
                @foreach ($schedules as $schedule)
                    @php
                        $scheduleWorkingDays = collect($schedule->working_days ?: [])->map(fn ($day) => (int) $day)->all();
                        $scheduleWeeklyHolidays = collect($schedule->weekly_holidays ?: [])->map(fn ($day) => (int) $day)->all();
                    @endphp
                    <tr>
                        <form method="POST" action="{{ route('hrat.employee-schedules.update', $schedule) }}">
                            @csrf @method('PUT')
                            <td style="min-width: 220px">
                                <select name="employee_id" class="form-control" required>
                                    <option value="">-- Select Employee --</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ (int) $schedule->employee_id === (int) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="min-width: 180px">
                                <select name="shift_id" class="form-control">
                                    <option value="">Use default config</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ (int) $schedule->shift_id === (int) $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="time" name="office_entry_time" value="{{ substr($schedule->office_entry_time ?? '', 0, 5) }}" class="form-control"></td>
                            <td><input type="time" name="entry_safe_time" value="{{ substr($schedule->entry_safe_time ?? '', 0, 5) }}" class="form-control"></td>
                            <td><input type="time" name="office_exit_time" value="{{ substr($schedule->office_exit_time ?? '', 0, 5) }}" class="form-control"></td>
                            <td><input type="time" name="early_exit_safe_time" value="{{ substr($schedule->early_exit_safe_time ?? '', 0, 5) }}" class="form-control"></td>
                            <td><input type="number" name="overtime_after_minutes" value="{{ $schedule->overtime_after_minutes }}" min="0" class="form-control"></td>
                            <td style="min-width: 170px">
                                <select name="overtime_calculation_method" class="form-control mb-1">
                                    <option value="">OT Method</option>
                                    <option value="from_exit_time" {{ $schedule->overtime_calculation_method === 'from_exit_time' ? 'selected' : '' }}>From exit</option>
                                    <option value="from_threshold_time" {{ $schedule->overtime_calculation_method === 'from_threshold_time' ? 'selected' : '' }}>From threshold</option>
                                </select>
                                <select name="late_calculation_method" class="form-control">
                                    <option value="">Late Method</option>
                                    <option value="from_entry_time" {{ $schedule->late_calculation_method === 'from_entry_time' ? 'selected' : '' }}>From entry</option>
                                    <option value="from_safe_time" {{ $schedule->late_calculation_method === 'from_safe_time' ? 'selected' : '' }}>From safe</option>
                                </select>
                            </td>
                            <td style="min-width: 240px">
                                <input type="date" name="effective_from" value="{{ optional($schedule->effective_from)->format('Y-m-d') }}" class="form-control mb-1" required>
                                <input type="date" name="effective_to" value="{{ optional($schedule->effective_to)->format('Y-m-d') }}" class="form-control">
                            </td>
                            <td style="min-width: 190px">
                                @foreach ($dayOptions as $day => $label)
                                    <label class="mr-1 mb-1"><input type="checkbox" name="working_days[]" value="{{ $day }}" {{ in_array($day, $scheduleWorkingDays, true) ? 'checked' : '' }}> {{ $label }}</label>
                                @endforeach
                                <small class="d-block text-muted">Blank = inherit</small>
                            </td>
                            <td style="min-width: 190px">
                                @foreach ($dayOptions as $day => $label)
                                    <label class="mr-1 mb-1"><input type="checkbox" name="weekly_holidays[]" value="{{ $day }}" {{ in_array($day, $scheduleWeeklyHolidays, true) ? 'checked' : '' }}> {{ $label }}</label>
                                @endforeach
                                <small class="d-block text-muted">Blank = inherit</small>
                            </td>
                            <td><label><input type="checkbox" name="is_active" value="1" {{ $schedule->is_active ? 'checked' : '' }}> Active</label></td>
                            <td>
                                <button class="btn btn-sm btn-info mb-1">Update</button>
                        </form>
                                <form method="POST" action="{{ route('hrat.employee-schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this employee schedule?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                    </tr>
                @endforeach
            </table>
        </div>
        {{ $schedules->links() }}
    </div></div>
@endsection
@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection
