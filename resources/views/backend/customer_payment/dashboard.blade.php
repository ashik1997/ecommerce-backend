@extends('backend.master')

@section('page_title')
    Customer Transaction Dashboard
@endsection

@section('page_heading')
    Customer Payments: At a Glance
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" action="{{ route('CustomerPaymentDashboard') }}" class="card card-body">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary mt-3" type="submit">Filter</button>
                        <a href="{{ route('CustomerTransactionReport', request()->only(['from_date', 'to_date'])) }}" class="btn btn-info mt-3">Report</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @php
            $cards = [
                ['title' => 'Collection', 'amount' => $summary['collection'], 'class' => 'success'],
                ['title' => 'Advance Received', 'amount' => $summary['advance'], 'class' => 'info'],
                ['title' => 'Refund', 'amount' => $summary['refund'], 'class' => 'warning'],
                ['title' => 'Net Collection', 'amount' => $summary['net_collection'], 'class' => 'primary'],
                ['title' => 'Total Due', 'amount' => $summary['total_due'], 'class' => 'danger'],
                ['title' => 'Order Due', 'amount' => $summary['order_due'], 'class' => 'secondary'],
                ['title' => 'Old Due', 'amount' => $summary['old_due'], 'class' => 'secondary'],
                ['title' => 'Advance Liability', 'amount' => $summary['advance_liability'], 'class' => 'dark'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="col-md-3 mb-3">
                <div class="card border-{{ $card['class'] }}">
                    <div class="card-body">
                        <small class="text-muted">{{ $card['title'] }}</small>
                        <h4 class="mb-0">৳{{ number_format($card['amount'], 2) }}</h4>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5>Payment Method Wise Collection</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($methodCollections as $row)
                                <tr>
                                    <td>{{ $row->payment_mode_title ?: 'N/A' }}</td>
                                    <td class="text-right">৳{{ number_format($row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center">No collection found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5>Top Due Customers</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th class="text-right">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topDueCustomers as $customer)
                                <tr>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->phone }}</td>
                                    <td class="text-right">৳{{ number_format($customer->due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No due customer found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5>Recent Customer Transactions</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Mode</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
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
                                <td class="text-right">৳{{ number_format($transaction->payment, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">No transaction found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
