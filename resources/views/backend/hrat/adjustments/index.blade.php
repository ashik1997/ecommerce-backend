@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Attendance Adjustments')
@section('page_heading', 'Attendance Adjustments')
@section('content')
    @php
        $auditValue = function ($data, $keys, $default = '-') {
            foreach ((array) $keys as $key) {
                $value = data_get($data, $key);
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            return $default;
        };
    @endphp
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.adjustments.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-2 mb-2">
                    <label>Type <span class="text-danger">*</span></label>
                    <select name="adjustment_type" class="form-control" required>
                        <option value="create">Create Log</option>
                        <option value="update">Update Log</option>
                        <option value="delete">Delete Log</option>
                        <option value="regenerate">Regenerate Summary</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Employee <span class="text-danger">*</span></label>
                    @include('backend.hrat.partials.employee-select', ['employees' => $employees, 'required' => true])
                </div>
                <div class="col-md-2 mb-2">
                    <label>Date <span class="text-danger">*</span></label>
                    <input type="date" name="attendance_date" class="form-control" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Requested Time</label>
                    <input type="time" name="requested_attendance_time" class="form-control">
                </div>
                <div class="col-md-2 mb-2">
                    <label>Punch Type</label>
                    <select name="requested_punch_type" class="form-control">
                        <option value="">Punch Type</option>
                        <option value="entry">Entry</option>
                        <option value="exit">Exit</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Existing Log</label>
                    <select name="attendance_log_id" class="form-control select2">
                        <option value="">No existing log</option>
                        @foreach ($logs as $log)
                            <option value="{{ $log->id }}">
                                #{{ $log->id }} - {{ $log->employee->name ?? 'N/A' }} - {{ optional($log->attendance_datetime)->format('Y-m-d H:i') }} - {{ ucfirst($log->punch_type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 mb-2">
                    <label>Reason <span class="text-danger">*</span></label>
                    <input name="reason" class="form-control" placeholder="Reason for correction" required>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-success">Request</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <tr>
                    <th>Employee</th><th>Date</th><th>Type</th><th>Requested</th><th>Existing Log</th><th>Audit</th><th>Status</th><th>Reason</th><th>Created / Reviewed</th><th>Action</th>
                </tr>
                @foreach ($adjustments as $item)
                    @php
                        $isPending = ($item->status ?? 'approved') === 'pending';
                        $formId = 'adjustment-update-' . $item->id;
                        $requestedTime = $item->requested_attendance_time ? \Carbon\Carbon::parse($item->requested_attendance_time)->format('H:i') : '';
                        $oldPunch = $auditValue($item->old_data, 'punch_type');
                        $oldTime = $auditValue($item->old_data, ['attendance_time', 'requested_attendance_time']);
                        $newPunch = $auditValue($item->new_data, 'punch_type');
                        $newTime = $auditValue($item->new_data, ['attendance_time', 'requested_attendance_time']);
                    @endphp
                    <tr>
                        <td style="min-width: 220px">
                            @if($isPending)
                                <select name="employee_id" class="form-control" form="{{ $formId }}" required>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ (int) $item->employee_id === (int) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{ $item->employee->name ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if($isPending)
                                <input type="date" name="attendance_date" value="{{ optional($item->attendance_date)->format('Y-m-d') }}" class="form-control" form="{{ $formId }}" required>
                            @else
                                {{ optional($item->attendance_date)->format('Y-m-d') }}
                            @endif
                        </td>
                        <td>
                            @if($isPending)
                                <select name="adjustment_type" class="form-control" form="{{ $formId }}" required>
                                    @foreach (['create' => 'Create Log', 'update' => 'Update Log', 'delete' => 'Delete Log', 'regenerate' => 'Regenerate Summary'] as $value => $label)
                                        <option value="{{ $value }}" {{ $item->adjustment_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{ ucfirst($item->adjustment_type) }}
                            @endif
                        </td>
                        <td style="min-width: 170px">
                            @if($isPending)
                                <select name="requested_punch_type" class="form-control mb-1" form="{{ $formId }}">
                                    <option value="">Punch Type</option>
                                    @foreach (['entry' => 'Entry', 'exit' => 'Exit'] as $value => $label)
                                        <option value="{{ $value }}" {{ $item->requested_punch_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="time" name="requested_attendance_time" value="{{ $requestedTime }}" class="form-control" form="{{ $formId }}">
                            @else
                                {{ $item->requested_punch_type ? ucfirst($item->requested_punch_type) : '-' }}
                                {{ $requestedTime ? ' @ ' . $requestedTime : '' }}
                            @endif
                        </td>
                        <td style="min-width: 260px">
                            @if($isPending)
                                <select name="attendance_log_id" class="form-control" form="{{ $formId }}">
                                    <option value="">No existing log</option>
                                    @if($item->attendanceLog && !$logs->contains('id', $item->attendance_log_id))
                                        <option value="{{ $item->attendanceLog->id }}" selected>
                                            #{{ $item->attendanceLog->id }} - {{ $item->attendanceLog->employee->name ?? 'N/A' }} - {{ optional($item->attendanceLog->attendance_datetime)->format('Y-m-d H:i') }} - {{ ucfirst($item->attendanceLog->punch_type) }}
                                        </option>
                                    @endif
                                    @foreach ($logs as $log)
                                        <option value="{{ $log->id }}" {{ (int) $item->attendance_log_id === (int) $log->id ? 'selected' : '' }}>
                                            #{{ $log->id }} - {{ $log->employee->name ?? 'N/A' }} - {{ optional($log->attendance_datetime)->format('Y-m-d H:i') }} - {{ ucfirst($log->punch_type) }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($item->attendance_log_id)
                                Log #{{ $item->attendance_log_id }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="min-width: 220px">
                            <div><strong>Old:</strong> {{ ucfirst($oldPunch) }} {{ $oldTime !== '-' ? '@ ' . \Carbon\Carbon::parse($oldTime)->format('H:i') : '' }}</div>
                            <div><strong>New:</strong> {{ ucfirst($newPunch) }} {{ $newTime !== '-' ? '@ ' . \Carbon\Carbon::parse($newTime)->format('H:i') : '' }}</div>
                            @if($item->review_note)
                                <div class="text-muted">Note: {{ $item->review_note }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $item->status === 'approved' ? 'success' : ($item->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($item->status ?? 'approved') }}
                            </span>
                        </td>
                        <td style="min-width: 220px">
                            @if($isPending)
                                <input name="reason" value="{{ $item->reason }}" class="form-control" form="{{ $formId }}" required>
                            @else
                                {{ $item->reason }}
                            @endif
                        </td>
                        <td style="min-width: 180px">
                            <div>{{ $item->creator->name ?? $item->created_by ?? '-' }}</div>
                            <div class="text-muted">{{ optional($item->created_at)->format('Y-m-d H:i') }}</div>
                            <hr class="my-1">
                            <div>{{ $item->reviewer->name ?? $item->reviewed_by ?? '-' }}</div>
                            @if($item->reviewed_at)
                                <div class="text-muted">{{ $item->reviewed_at->format('Y-m-d H:i') }}</div>
                            @endif
                        </td>
                        <td style="min-width: 120px">
                            @if($isPending)
                                <form id="{{ $formId }}" method="POST" action="{{ route('hrat.adjustments.update', $item) }}" class="mb-1">
                                    @csrf @method('PUT')
                                    <button class="btn btn-sm btn-info">Update</button>
                                </form>
                                <form method="POST" action="{{ route('hrat.adjustments.approve', $item) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success mb-1">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('hrat.adjustments.reject', $item) }}" class="mb-1">
                                    @csrf
                                    <input type="hidden" name="review_note" value="Rejected from adjustment list">
                                    <button class="btn btn-sm btn-warning">Reject</button>
                                </form>
                                <form method="POST" action="{{ route('hrat.adjustments.destroy', $item) }}" onsubmit="return confirm('Delete this adjustment request?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @else
                                {{ optional($item->updated_at)->format('Y-m-d H:i') }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        {{ $adjustments->links() }}
    </div></div>
@endsection
@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection
