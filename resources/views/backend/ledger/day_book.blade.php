@extends('backend.master')

@section('page_title')
    Day Book
@endsection

@section('page_heading')
    Day Book
@endsection

@section('header_css')
    <style>
        @media print {
            form, .no-print {
                display: none !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Day Book / Daily Summary</h4>

                    <form method="GET" action="{{ route('ledger.day_book') }}" class="mb-3">
                        <div class="row">
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                        value="{{ request('start_date', $startDate ?? now('Asia/Dhaka')->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control"
                                        value="{{ request('end_date', $endDate ?? now('Asia/Dhaka')->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="store_id">Store ID</label>
                                    <input type="number" id="store_id" name="store_id" class="form-control"
                                        value="{{ request('store_id') }}">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="transaction_type">Transaction Type</label>
                                    <input type="text" id="transaction_type" name="transaction_type" class="form-control"
                                        value="{{ request('transaction_type') }}">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="event_type">Event Type</label>
                                    <input type="text" id="event_type" name="event_type" class="form-control"
                                        value="{{ request('event_type') }}">
                                </div>
                            </div>
                            <div class="col-lg-3 d-flex align-items-end">
                                <div class="form-group">
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                    <button class="btn btn-secondary" type="button" onclick="window.print()">Print</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    @php
                        $rows = collect($dayBook['rows'] ?? []);
                        $dailySummary = $dayBook['daily_summary'] ?? [];
                        $totals = $dayBook['totals'] ?? [];
                    @endphp

                    <div class="mb-3">
                        <strong>Period:</strong> {{ $startDate ?? '' }} to {{ $endDate ?? '' }}
                        @if (request('store_id'))
                            <span class="ml-3"><strong>Store ID:</strong> {{ request('store_id') }}</span>
                        @endif
                    </div>

                    <h5 class="text-primary">Daily Summary</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-right">Purchase</th>
                                    <th class="text-right">Sales</th>
                                    <th class="text-right">Expense</th>
                                    <th class="text-right">Collection</th>
                                    <th class="text-right">Payment</th>
                                    <th class="text-right">Net Cash Movement</th>
                                    <th class="text-right">Debit Total</th>
                                    <th class="text-right">Credit Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dailySummary as $date => $summary)
                                    <tr>
                                        <td>{{ $date }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['purchase'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['sales'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['expense'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['collection'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['payment'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['net_cash_movement'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['total_debit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($summary['total_credit'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No daily summary found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($totals['purchase'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['sales'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['expense'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['collection'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['payment'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['net_cash_movement'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['total_debit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['total_credit'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <h5 class="text-primary">Transaction Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">SL</th>
                                    <th>Date</th>
                                    <th>Payment Code</th>
                                    <th>Transaction Type</th>
                                    <th>Event Type</th>
                                    <th>Debit Account</th>
                                    <th>Credit Account</th>
                                    <th class="text-right">Debit Amount</th>
                                    <th class="text-right">Credit Amount</th>
                                    <th>Note</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $transaction = $row['transaction'];
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $transaction->transaction_date }}</td>
                                        <td>{{ $transaction->payment_code ?? '-' }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $transaction->transaction_type ?? '-')) }}</td>
                                        <td>{{ $transaction->event_type ?? '-' }}</td>
                                        <td>{{ $row['debit_account_name'] ?: '-' }}</td>
                                        <td>{{ $row['credit_account_name'] ?: '-' }}</td>
                                        <td class="text-right">৳ {{ number_format($row['debit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['credit'] ?? 0, 2) }}</td>
                                        <td>{{ $transaction->note ?? '-' }}</td>
                                        <td>{{ $transaction->created_by ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No transaction found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="7" class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($totals['total_debit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['total_credit'] ?? 0, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
