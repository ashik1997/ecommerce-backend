@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('page_title')
    Customer Transaction Report
@endsection

@section('page_heading')
    Customer Transaction Report
@endsection

@section('content')
    <form method="GET" action="{{ route('CustomerTransactionReport') }}" class="card card-body mb-3">
        <div class="row align-items-end">
            <div class="col-md-2">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-md-2">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-md-3">
                <label>Customer</label>
                <select name="customer_id" class="form-control select2">
                    <option value="">All Customers</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (string) $customerId === (string) $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} - {{ $customer->phone }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Type</label>
                <select name="payment_type" class="form-control">
                    <option value="">All</option>
                    @foreach(['received' => 'Due Collection', 'advance' => 'Advance Received', 'adjustment' => 'Advance Applied', 'refund' => 'Refund'] as $key => $label)
                        <option value="{{ $key }}" {{ $paymentType === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Payment Method</label>
                <select name="payment_mode" class="form-control">
                    <option value="">All</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}" {{ (string) $paymentMode === (string) $method->id ? 'selected' : '' }}>
                            {{ $method->payment_type }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Method</th>
                            <th>Debit Account</th>
                            <th>Credit Account</th>
                            <th class="text-right">Amount</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            @php
                                $rows = $accountRows->get($transaction->id, collect());
                                $debitRow = $rows->firstWhere('debit_account_id', '!=', null);
                                $creditRow = $rows->firstWhere('credit_account_id', '!=', null);
                            @endphp
                            <tr>
                                <td>{{ $transaction->payment_date }}</td>
                                <td>{{ $transaction->customer->name ?? 'N/A' }}</td>
                                <td>{{ ucfirst($transaction->payment_type) }}</td>
                                <td>
                                    @if($transaction->order)
                                        {{ $transaction->order->order_code }}
                                    @elseif($transaction->openingBalance)
                                        {{ $transaction->openingBalance->reference_no ?: 'OLD-DUE-'.$transaction->openingBalance->id }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $transaction->payment_mode_title ?: 'N/A' }}</td>
                                <td>{{ $debitRow->debitAccount->account_name ?? '-' }}</td>
                                <td>{{ $creditRow->creditAccount->account_name ?? '-' }}</td>
                                <td class="text-right">৳{{ number_format($transaction->payment, 2) }}</td>
                                <td>{{ $transaction->payment_note }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center">No transaction found</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-right">Total</th>
                            <th class="text-right">৳{{ number_format($transactions->sum('payment'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        $('.select2').select2();
    </script>
@endsection
