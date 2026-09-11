@extends('backend.master')
@section('page_title', 'Payrolls')
@section('page_heading', 'Payrolls')
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.payrolls.generate') }}" class="row">
            @csrf
            <div class="col-md-2"><input type="number" name="year" value="{{ now()->year }}" class="form-control" required></div>
            <div class="col-md-2"><input type="number" name="month" value="{{ now()->month }}" min="1" max="12" class="form-control" required></div>
            <div class="col-md-2"><button class="btn btn-success">Generate Draft</button></div>
        </form>
    </div></div>
    @foreach($payrolls as $payroll)
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">{{ $payroll->year }}-{{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }} ({{ ucfirst($payroll->status) }})</h5>
                <div>
                    <strong>Total: {{ number_format($payroll->totalNetPayable(), 2) }}</strong>
                    @if($payroll->status === 'draft')
                        <form method="POST" action="{{ route('hrat.payrolls.approve', $payroll) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                    @elseif($payroll->status === 'approved')
                        <form method="POST" action="{{ route('hrat.payrolls.finalize', $payroll) }}" class="form-inline d-inline-flex">
                            @csrf
                            <select name="expense_account_id" class="form-control form-control-sm mr-1" required>
                                <option value="">Salary Expense</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                @endforeach
                            </select>
                            <select name="payable_account_id" class="form-control form-control-sm mr-1" required>
                                <option value="">Salary Payable</option>
                                @foreach($payableAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-warning">Finalize + Post</button>
                        </form>
                    @elseif($payroll->status === 'finalized')
                        <form method="POST" action="{{ route('hrat.payrolls.mark-paid', $payroll) }}" class="form-inline d-inline-flex">
                            @csrf
                            <select name="payment_type_id" class="form-control form-control-sm mr-1" required>
                                <option value="">Payment Type</option>
                                @foreach($paymentTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->payment_type }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary">Mark Paid</button>
                        </form>
                        <form method="POST" action="{{ route('hrat.payrolls.void-finalization', $payroll) }}" class="d-inline" onsubmit="return confirm('Void payroll finalization and reverse accrual entries?')">
                            @csrf
                            <button class="btn btn-sm btn-danger">Void Finalization</button>
                        </form>
                    @elseif($payroll->status === 'paid')
                        <form method="POST" action="{{ route('hrat.payrolls.reverse-payment', $payroll) }}" class="d-inline" onsubmit="return confirm('Reverse this payroll payment?')">
                            @csrf
                            <button class="btn btn-sm btn-danger">Reverse Payment</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="mb-2 text-muted small">
                Accounting: {{ $payroll->accounting_posted_at ? 'Posted (' . $payroll->accounting_reference . ')' : 'Not posted' }}
                | Payment: {{ $payroll->payment_posted_at ? 'Posted (' . $payroll->payment_reference . ')' : 'Not posted' }}
            </div>
            <table class="table table-sm table-bordered">
                <tr><th>Employee</th><th>Basic</th><th>Allowance</th><th>Attendance</th><th>OT</th><th>Deduction</th><th>Net</th><th>Payslip</th></tr>
                @foreach($payroll->lines as $line)
                    @php
                        $meta = $line->meta ?: [];
                    @endphp
                    <tr>
                        <td>{{ $line->employee->name ?? 'N/A' }}</td>
                        <td>{{ number_format($line->basic_salary,2) }}</td>
                        <td>{{ number_format($line->allowance_total,2) }}</td>
                        <td>P: {{ $line->present_days }} | A: {{ $line->absent_days }} | L: {{ $line->late_days }}</td>
                        <td>{{ $line->overtime_minutes }} min / {{ number_format($line->overtime_amount,2) }}</td>
                        <td>
                            {{ number_format($line->deduction_total + $line->absent_deduction,2) }}
                            <div class="text-muted small">
                                Component: {{ number_format($meta['component_deductions'] ?? 0, 2) }},
                                Late: {{ number_format($meta['late_deduction'] ?? 0, 2) }},
                                Unpaid leave: {{ number_format($meta['unpaid_leave_deduction'] ?? 0, 2) }},
                                Absent: {{ number_format($line->absent_deduction, 2) }}
                            </div>
                        </td>
                        <td>{{ number_format($line->net_payable,2) }}</td>
                        <td><a href="{{ route('hrat.payrolls.payslip', [$payroll, $line]) }}" class="btn btn-sm btn-info">View</a></td>
                    </tr>
                @endforeach
            </table>
            @include('backend.hrat.payrolls._accounting_entries', ['payroll' => $payroll])
        </div></div>
    @endforeach
    {{ $payrolls->links() }}
@endsection
