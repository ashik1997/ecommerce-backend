@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Leave Applications')
@section('page_heading', 'Leave Applications')
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.leave-applications.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label>Employee <span class="text-danger">*</span></label>
                    @include('backend.hrat.partials.employee-select', ['employees' => $employees, 'required' => true])
                </div>
                <div class="col-md-2 mb-2">
                    <label>Leave Type <span class="text-danger">*</span></label>
                    <select name="leave_type_id" class="form-control" required>
                        <option value="">Leave Type</option>
                        @foreach ($leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2"><label>From <span class="text-danger">*</span></label><input type="date" name="from_date" class="form-control" required></div>
                <div class="col-md-2 mb-2"><label>To <span class="text-danger">*</span></label><input type="date" name="to_date" class="form-control" required></div>
                <div class="col-md-2 mb-2"><label>Reason</label><input name="reason" class="form-control" placeholder="Reason"></div>
                <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-success">Apply</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <h5>Current Year Leave Balances</h5>
        <table class="table table-bordered table-sm">
            <tr><th>Employee</th><th>Leave Type</th><th>Allocated</th><th>Used</th><th>Remaining</th><th>Year</th></tr>
            @foreach ($balances as $balance)
                <tr>
                    <td>{{ $balance->employee->name ?? 'N/A' }}</td>
                    <td>{{ $balance->leaveType->name ?? 'N/A' }}</td>
                    <td>{{ number_format($balance->allocated_days, 2) }}</td>
                    <td>{{ number_format($balance->used_days, 2) }}</td>
                    <td>{{ number_format($balance->remaining_days, 2) }}</td>
                    <td>{{ $balance->year }}</td>
                </tr>
            @endforeach
        </table>
    </div></div>

    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr><th>Employee</th><th>Type</th><th>Period</th><th>Days</th><th>Status</th><th>Reason</th><th>Reviewed By</th><th>Review Note</th><th>Action</th></tr>
            @foreach ($applications as $item)
                <tr>
                    <td>{{ $item->employee->name ?? 'N/A' }}</td>
                    <td>{{ $item->leaveType->name ?? 'N/A' }}</td>
                    <td>{{ optional($item->from_date)->format('Y-m-d') }} - {{ optional($item->to_date)->format('Y-m-d') }}</td>
                    <td>{{ number_format($item->total_days, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $item->status === 'approved' ? 'success' : ($item->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($item->status) }}</span>
                    </td>
                    <td>{{ $item->reason }}</td>
                    <td>{{ $item->approver->name ?? $item->rejecter->name ?? '-' }}</td>
                    <td>{{ $item->review_note ?? '-' }}</td>
                    <td>
                        @if($item->status === 'pending')
                            <form method="POST" action="{{ route('hrat.leave-applications.approve', $item) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success mb-1">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('hrat.leave-applications.reject', $item) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="review_note" value="Rejected from leave list">
                                <button class="btn btn-sm btn-danger mb-1">Reject</button>
                            </form>
                            <form method="POST" action="{{ route('hrat.leave-applications.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this leave application?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-secondary mb-1">Delete</button>
                            </form>
                        @elseif($item->status === 'approved')
                            <form method="POST" action="{{ route('hrat.leave-applications.cancel', $item) }}" class="d-inline" onsubmit="return confirm('Cancel this approved leave and restore balance?')">
                                @csrf
                                <input type="hidden" name="review_note" value="Cancelled from leave list">
                                <button class="btn btn-sm btn-warning mb-1">Cancel</button>
                            </form>
                            <div class="text-muted">{{ $item->approved_at }}</div>
                        @else
                            {{ $item->approved_at ?: $item->rejected_at }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        {{ $applications->links() }}
    </div></div>
@endsection
@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection
