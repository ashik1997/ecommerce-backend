@extends('backend.master')

@section('page_title', 'Payslip')
@section('page_heading', 'Payslip')

@section('content')
    @php
        $meta = $line->meta ?: [];
        $profile = optional($line->employee)->employeeProfile;
        $components = collect($meta['components'] ?? []);
        $allowanceComponents = $components->where('type', 'allowance');
        $deductionComponents = $components->where('type', 'deduction');
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-1">Payslip</h4>
                    <div class="text-muted">
                        Payroll: {{ $payroll->year }}-{{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }}
                        | Cycle: {{ optional($payroll->from_date)->format('Y-m-d') }} to {{ optional($payroll->to_date)->format('Y-m-d') }}
                    </div>
                </div>
                <button onclick="window.print()" class="btn btn-secondary">Print</button>
            </div>

            <table class="table table-bordered table-sm">
                <tr>
                    <th>Employee</th><td>{{ $line->employee->name ?? 'N/A' }}</td>
                    <th>Employee Code</th><td>{{ $profile->employee_code ?? $line->employee_id }}</td>
                </tr>
                <tr>
                    <th>Department</th><td>{{ $profile->department ?? '-' }}</td>
                    <th>Designation</th><td>{{ $profile->designation ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Branch</th><td>{{ $profile->branch ?? '-' }}</td>
                    <th>Status</th><td>{{ ucfirst($payroll->status) }}</td>
                </tr>
            </table>

            <div class="row">
                <div class="col-md-6">
                    <h5>Earnings</h5>
                    <table class="table table-bordered table-sm">
                        <tr><th>Head</th><th class="text-right">Amount</th></tr>
                        <tr><td>Basic Salary</td><td class="text-right">{{ number_format($line->basic_salary, 2) }}</td></tr>
                        @foreach ($allowanceComponents as $component)
                            <tr>
                                <td>{{ $component['name'] ?? 'Allowance' }}</td>
                                <td class="text-right">{{ number_format($component['calculated_amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr><td>Overtime</td><td class="text-right">{{ number_format($line->overtime_amount, 2) }}</td></tr>
                        <tr><th>Total Earnings</th><th class="text-right">{{ number_format($line->basic_salary + $line->allowance_total + $line->overtime_amount, 2) }}</th></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Deductions</h5>
                    <table class="table table-bordered table-sm">
                        <tr><th>Head</th><th class="text-right">Amount</th></tr>
                        @foreach ($deductionComponents as $component)
                            <tr>
                                <td>{{ $component['name'] ?? 'Deduction' }}</td>
                                <td class="text-right">{{ number_format($component['calculated_amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr><td>Absent Deduction</td><td class="text-right">{{ number_format($line->absent_deduction, 2) }}</td></tr>
                        <tr><td>Late Deduction</td><td class="text-right">{{ number_format($meta['late_deduction'] ?? 0, 2) }}</td></tr>
                        <tr><td>Unpaid Leave Deduction</td><td class="text-right">{{ number_format($meta['unpaid_leave_deduction'] ?? 0, 2) }}</td></tr>
                        <tr><th>Total Deductions</th><th class="text-right">{{ number_format($line->deduction_total + $line->absent_deduction, 2) }}</th></tr>
                    </table>
                </div>
            </div>

            <table class="table table-bordered table-sm">
                <tr>
                    <th>Present</th><th>Absent</th><th>Late</th><th>Overtime</th><th>Unpaid Leave</th><th>Net Payable</th>
                </tr>
                <tr>
                    <td>{{ $line->present_days }}</td>
                    <td>{{ $line->absent_days }}</td>
                    <td>{{ $line->late_days }} days / {{ $meta['late_minutes'] ?? 0 }} min</td>
                    <td>{{ $line->overtime_minutes }} min</td>
                    <td>{{ $meta['unpaid_leave_days'] ?? 0 }}</td>
                    <td><strong>{{ number_format($line->net_payable, 2) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>
@endsection
