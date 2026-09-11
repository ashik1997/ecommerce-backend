@extends('backend.master')

@section('page_title')
    Customer Ledger
@endsection

@section('page_heading')
    Customer Ledger
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
                    <h4 class="card-title mb-3">Customer Ledger</h4>

                    <form method="GET" action="{{ route('ledger.customer_ledger') }}" class="mb-3">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="customer_id">Customer</label>
                                    <select id="customer_id" name="customer_id" class="form-control">
                                        <option value="">All Customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                        value="{{ request('start_date', $startDate ?? now('Asia/Dhaka')->subDays(30)->format('Y-m-d')) }}">
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
                            <div class="col-lg-3 d-flex align-items-end">
                                <div class="form-group">
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                    <button class="btn btn-secondary" type="button" onclick="window.print()">Print</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    @php
                        $rows = collect($customerLedger['rows'] ?? []);
                    @endphp

                    <div class="mb-3">
                        <strong>Period:</strong> {{ $startDate ?? '' }} to {{ $endDate ?? '' }}
                        @if ($selectedCustomer)
                            <span class="ml-3"><strong>Customer:</strong> {{ $selectedCustomer->name }}</span>
                        @endif
                        @if (request('store_id'))
                            <span class="ml-3"><strong>Store ID:</strong> {{ request('store_id') }}</span>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">SL</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Payment Code</th>
                                    <th>Type</th>
                                    <th>Receivable Account</th>
                                    <th>Opposite Account</th>
                                    <th class="text-right">Debit / Due</th>
                                    <th class="text-right">Credit / Received</th>
                                    <th class="text-right">Balance Due</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="font-weight-bold">
                                    <td colspan="9" class="text-right">Opening Balance</td>
                                    <td class="text-right">৳ {{ number_format($customerLedger['opening_balance'] ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>

                                @forelse ($rows as $row)
                                    @php
                                        $transaction = $row['transaction'];
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $transaction->transaction_date }}</td>
                                        <td>{{ $row['customer_name'] ?: '-' }}</td>
                                        <td>{{ $transaction->payment_code ?? '-' }}</td>
                                        <td>
                                            {{ ucwords(str_replace('_', ' ', $transaction->transaction_type ?? '-')) }}
                                            @if ($transaction->event_type)
                                                <br><small class="text-muted">{{ $transaction->event_type }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $row['receivable_account_name'] ?: '-' }}</td>
                                        <td>{{ $row['opposite_account_name'] ?: '-' }}</td>
                                        <td class="text-right">৳ {{ number_format($row['debit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['credit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['running_balance'] ?? 0, 2) }}</td>
                                        <td>{{ $transaction->note ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No customer receivable transaction found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="7" class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($customerLedger['total_debit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($customerLedger['total_credit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($customerLedger['closing_balance'] ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
