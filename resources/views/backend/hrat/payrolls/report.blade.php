@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Payroll Report')
@section('page_heading', 'Payroll Report')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET">
                <div class="row">
                    <div class="col-md-2 mb-2"><label>Year</label><input type="number" name="year" value="{{ request('year') }}" class="form-control"></div>
                    <div class="col-md-2 mb-2"><label>Month</label><input type="number" name="month" value="{{ request('month') }}" min="1" max="12" class="form-control"></div>
                    <div class="col-md-2 mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach (['draft', 'approved', 'finalized', 'paid'] as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><label>Employee</label>@include('backend.hrat.partials.employee-select', ['employees' => $employees, 'selected' => request('employee_id')])</div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button class="btn btn-secondary mr-2">Filter</button>
                        <a href="{{ route('hrat.payroll-reports.export.csv', request()->query()) }}" class="btn btn-success">CSV Export</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @foreach ($payrolls as $payroll)
        <div class="card mb-3">
            <div class="card-body table-responsive">
                <div class="d-flex justify-content-between mb-2">
                    <div>
                        <h5 class="mb-0">{{ $payroll->year }}-{{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }} Payroll</h5>
                        <div class="text-muted">{{ optional($payroll->from_date)->format('Y-m-d') }} to {{ optional($payroll->to_date)->format('Y-m-d') }} | {{ ucfirst($payroll->status) }}</div>
                    </div>
                    <strong>Total Net: {{ number_format($payroll->totalNetPayable(), 2) }}</strong>
                </div>
                <table class="table table-bordered table-sm">
                    <tr>
                        <th>Employee</th><th>Basic</th><th>Allowance</th><th>Deductions</th><th>Absent</th><th>Late</th><th>Unpaid Leave</th><th>Overtime</th><th>Net</th><th>Payslip</th>
                    </tr>
                    @foreach ($payroll->lines as $line)
                        @php($meta = $line->meta ?: [])
                        <tr>
                            <td>{{ $line->employee->name ?? 'N/A' }}</td>
                            <td>{{ number_format($line->basic_salary, 2) }}</td>
                            <td>{{ number_format($line->allowance_total, 2) }}</td>
                            <td>
                                {{ number_format($line->deduction_total + $line->absent_deduction, 2) }}
                                <div class="text-muted small">Component: {{ number_format($meta['component_deductions'] ?? 0, 2) }}</div>
                            </td>
                            <td>{{ $line->absent_days }} / {{ number_format($line->absent_deduction, 2) }}</td>
                            <td>{{ $line->late_days }} days, {{ $meta['late_minutes'] ?? 0 }} min / {{ number_format($meta['late_deduction'] ?? 0, 2) }}</td>
                            <td>{{ $meta['unpaid_leave_days'] ?? 0 }} / {{ number_format($meta['unpaid_leave_deduction'] ?? 0, 2) }}</td>
                            <td>{{ $line->overtime_minutes }} min / {{ number_format($line->overtime_amount, 2) }}</td>
                            <td><strong>{{ number_format($line->net_payable, 2) }}</strong></td>
                            <td><a href="{{ route('hrat.payrolls.payslip', [$payroll, $line]) }}" class="btn btn-sm btn-info">View</a></td>
                        </tr>
                    @endforeach
                </table>
                @include('backend.hrat.payrolls._accounting_entries', ['payroll' => $payroll])
            </div>
        </div>
    @endforeach
    {{ $payrolls->links() }}
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection
