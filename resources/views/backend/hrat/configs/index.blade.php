@extends('backend.master')

@section('page_title', 'Attendance Configuration')
@section('page_heading', 'Attendance Configuration')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hrat.configs.store') }}">
                @csrf
                <div class="row">
                    @foreach ([
                        'office_entry_time' => 'Office Entry Time',
                        'entry_safe_time' => 'Entry Safe Time',
                        'office_exit_time' => 'Office Exit Time',
                        'early_exit_safe_time' => 'Early Exit Safe Time',
                        'entry_button_start_time' => 'Entry Button Start',
                        'entry_button_end_time' => 'Entry Button End',
                        'exit_button_start_time' => 'Exit Button Start',
                        'exit_button_end_time' => 'Exit Button End',
                    ] as $field => $label)
                        <div class="col-md-3 mb-3">
                            <label>{{ $label }}</label>
                            <input type="time" name="{{ $field }}" value="{{ old($field, substr($config->$field, 0, 5)) }}" class="form-control" required>
                        </div>
                    @endforeach
                    <div class="col-md-3 mb-3">
                        <label>Overtime After Minutes</label>
                        <input type="number" name="overtime_after_minutes" value="{{ old('overtime_after_minutes', $config->overtime_after_minutes) }}" class="form-control" min="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Overtime Calculation</label>
                        <select name="overtime_calculation_method" class="form-control">
                            <option value="from_exit_time" {{ $config->overtime_calculation_method === 'from_exit_time' ? 'selected' : '' }}>From Office Exit Time</option>
                            <option value="from_threshold_time" {{ $config->overtime_calculation_method === 'from_threshold_time' ? 'selected' : '' }}>From Threshold Time</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Late Calculation</label>
                        <select name="late_calculation_method" class="form-control">
                            <option value="from_entry_time" {{ $config->late_calculation_method === 'from_entry_time' ? 'selected' : '' }}>From Office Entry Time</option>
                            <option value="from_safe_time" {{ $config->late_calculation_method === 'from_safe_time' ? 'selected' : '' }}>From Safe Time</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Month Start Day</label>
                        <input type="number" name="month_start_day" value="{{ old('month_start_day', $config->month_start_day) }}" class="form-control" min="1" max="31">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Month End Day</label>
                        <input type="number" name="month_end_day" value="{{ old('month_end_day', $config->month_end_day) }}" class="form-control" min="1" max="31">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Timezone</label>
                        <input type="text" name="timezone" value="{{ old('timezone', $config->timezone) }}" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="mr-3"><input type="checkbox" name="allow_duplicate_employee_panel_entry" value="1" {{ $config->allow_duplicate_employee_panel_entry ? 'checked' : '' }}> Allow duplicate employee panel entry</label>
                        <label><input type="checkbox" name="allow_exit_without_entry" value="1" {{ $config->allow_exit_without_entry ? 'checked' : '' }}> Allow exit without entry</label>
                    </div>
                </div>
                <button class="btn btn-success">Save Configuration</button>
            </form>
        </div>
    </div>
@endsection
