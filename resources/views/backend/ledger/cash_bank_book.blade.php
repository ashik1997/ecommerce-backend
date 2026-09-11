@extends('backend.master')

@section('page_title')
    Cash / Bank Book
@endsection

@section('page_heading')
    Cash / Bank Book
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
                    <h4 class="card-title mb-3">Cash / Bank Book</h4>

                    <form method="GET" action="{{ route('ledger.cash_bank_book') }}" class="mb-3">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="account_id">Cash / Bank Account</label>
                                    <select id="account_id" name="account_id" class="form-control">
                                        <option value="">All Cash / Bank Accounts</option>
                                        @foreach ($cashBankAccounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ (string) request('account_id') === (string) $account->id ? 'selected' : '' }}>
                                                {{ $account->sort_code ? $account->sort_code . ' - ' : '' }}{{ $account->account_name }}
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
                        $rows = collect($cashBankBook['rows'] ?? []);
                    @endphp

                    <div class="mb-3">
                        <strong>Period:</strong> {{ $startDate ?? '' }} to {{ $endDate ?? '' }}
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
                                    <th>Payment Code</th>
                                    <th>Type</th>
                                    <th>Cash / Bank Account</th>
                                    <th>Opposite Account</th>
                                    <th class="text-right">Inflow</th>
                                    <th class="text-right">Outflow</th>
                                    <th class="text-right">Running Balance</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="font-weight-bold">
                                    <td colspan="8" class="text-right">Opening Balance</td>
                                    <td class="text-right">৳ {{ number_format($cashBankBook['opening_balance'] ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>

                                @forelse ($rows as $row)
                                    @php
                                        $transaction = $row['transaction'];
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $transaction->transaction_date }}</td>
                                        <td>{{ $transaction->payment_code ?? '-' }}</td>
                                        <td>
                                            {{ ucwords(str_replace('_', ' ', $transaction->transaction_type ?? '-')) }}
                                            @if ($transaction->event_type)
                                                <br><small class="text-muted">{{ $transaction->event_type }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $row['account_name'] ?: '-' }}</td>
                                        <td>{{ $row['opposite_account_name'] ?: '-' }}</td>
                                        <td class="text-right">৳ {{ number_format($row['inflow'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['outflow'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['running_balance'] ?? 0, 2) }}</td>
                                        <td>{{ $transaction->note ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No cash or bank transaction found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="6" class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($cashBankBook['total_inflow'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($cashBankBook['total_outflow'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($cashBankBook['closing_balance'] ?? 0, 2) }}</td>
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
