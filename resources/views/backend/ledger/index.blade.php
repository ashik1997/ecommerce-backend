@extends('backend.master')

@section('page_title')
    Ledger
@endsection

@section('page_heading')
    @if (isset($account))
        Ledger for {{ $account->account_name }}
    @else
        Ledger for All Accounts
    @endif
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
                    <h4 class="card-title mb-3">Ledger Entries</h4>

                    <!-- Mini Form for Filtering -->
                    <form method="GET" action="{{ route('ledger.index') }}">
                        @csrf
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="account_id">Account<span class="text-danger">*</span></label>
                                    <select id="account_id" name="account_id" class="form-control">
                                        <option value="">All Accounts</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}"
                                                {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                                {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                        value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control"
                                        value="{{ request('end_date', now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="store_id">Store ID</label>
                                    <input type="number" id="store_id" name="store_id" class="form-control"
                                        value="{{ request('store_id') }}">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="customer_id">Customer ID</label>
                                    <input type="number" id="customer_id" name="customer_id" class="form-control"
                                        value="{{ request('customer_id') }}">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="supplier_id">Supplier ID</label>
                                    <input type="number" id="supplier_id" name="supplier_id" class="form-control"
                                        value="{{ request('supplier_id') }}">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="transaction_type">Transaction Type</label>
                                    <input type="text" id="transaction_type" name="transaction_type" class="form-control"
                                        value="{{ request('transaction_type') }}">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group">
                                    <label for="event_type">Event Type</label>
                                    <input type="text" id="event_type" name="event_type" class="form-control"
                                        value="{{ request('event_type') }}">
                                </div>
                            </div>
                            <div class="col-lg-2 d-flex align-items-end">
                                <div class="form-group">
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                    <button class="btn btn-secondary" type="button" onclick="window.print()">Print</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Ledger Table -->
                    @if (isset($account))
                        <!-- Display ledger for a single account -->
                        <hr>
                        <h4 class="card-title mb-3">Ledger for {{ $account->account_name }}</h4>
                        @if ($transactions->isEmpty())
                            <p>No transactions found for this account.</p>
                        @else
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payment Code</th>
                                        <th>Type</th>
                                        <th>Opposite Account</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                        <th colspan="2">Balance</th>
                                    </tr>
                                    <tr>
                                        <th colspan="6"></th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $normalBalance = $account->normal_balance ?: (in_array($account->account_type, ['asset', 'expense']) ? 'debit' : 'credit');
                                        $runningBalance = (float) ($openingBalance ?? 0);
                                        $totalDebit = 0;
                                        $totalCredit = 0;
                                    @endphp
                                    <tr class="table-light">
                                        <td>{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}</td>
                                        <td>Opening Balance</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>{{ (($normalBalance === 'debit' && $runningBalance >= 0) || ($normalBalance === 'credit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                        <td>{{ (($normalBalance === 'credit' && $runningBalance >= 0) || ($normalBalance === 'debit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                    </tr>
                                    @foreach ($transactions as $transaction)
                                        @php
                                            if ($transaction->debit_account_id == $account->id) {
                                                $debit = $transaction->debit_amt;
                                                $credit = 0;
                                                $runningBalance += $normalBalance === 'debit' ? $debit : -$debit;
                                            } else {
                                                $debit = 0;
                                                $credit = $transaction->credit_amt;
                                                $runningBalance += $normalBalance === 'credit' ? $credit : -$credit;
                                            }
                                            $totalDebit += $debit;
                                            $totalCredit += $credit;
                                        @endphp
                                        <tr>
                                            <td>{{ $transaction->transaction_date }}</td>
                                            <td>{{ $transaction->payment_code }}</td>
                                            <td>{{ $transaction->transaction_type }}</td>
                                            <td>
                                                @if ($transaction->debit_account_id == $account->id)
                                                    {{ $transaction->creditAccount->account_name ?? 'N/A' }}
                                                @else
                                                    {{ $transaction->debitAccount->account_name ?? 'N/A' }}
                                                @endif
                                                @if ($transaction->note)
                                                    <div class="text-muted small">{{ $transaction->note }}</div>
                                                @endif
                                            </td>
                                            <td>{{ number_format($debit, 2) }}</td>
                                            <td>{{ number_format($credit, 2) }}</td>
                                            <td>{{ (($normalBalance === 'debit' && $runningBalance >= 0) || ($normalBalance === 'credit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                            <td>{{ (($normalBalance === 'credit' && $runningBalance >= 0) || ($normalBalance === 'debit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <th colspan="4">Total</th>
                                        <th>{{ number_format($totalDebit, 2) }}</th>
                                        <th>{{ number_format($totalCredit, 2) }}</th>
                                        <th colspan="2">
                                            @php
                                                $netTotal = $runningBalance;
                                            @endphp
                                            @if (($normalBalance === 'debit' && $netTotal >= 0) || ($normalBalance === 'credit' && $netTotal < 0))
                                                <span>Debit: {{ number_format(abs($netTotal), 2) }}</span>
                                            @else
                                                <span>Credit: {{ number_format(abs($netTotal), 2) }}</span>
                                            @endif
                                        </th>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                    @elseif(isset($allTransactions))
                        <!-- Display ledger for all accounts -->
                        @foreach ($allTransactions as $data)
                            <hr>
                            <h4 class="card-title mb-3">Ledger for {{ $data['account']->account_name }}</h4>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payment Code</th>
                                        <th>Type</th>
                                        <th>Opposite Account</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                        <th colspan="2">Balance</th>
                                    </tr>
                                    <tr>
                                        <th colspan="6"></th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $normalBalance = $data['account']->normal_balance ?: (in_array($data['account']->account_type, ['asset', 'expense']) ? 'debit' : 'credit');
                                        $runningBalance = (float) ($data['opening_balance'] ?? 0);
                                        $totalDebit = 0;
                                        $totalCredit = 0;
                                    @endphp
                                    <tr class="table-light">
                                        <td>{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}</td>
                                        <td>Opening Balance</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>{{ (($normalBalance === 'debit' && $runningBalance >= 0) || ($normalBalance === 'credit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                        <td>{{ (($normalBalance === 'credit' && $runningBalance >= 0) || ($normalBalance === 'debit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                    </tr>
                                    @foreach ($data['transactions'] as $transaction)
                                        @php
                                            if ($transaction->debit_account_id == $data['account']->id) {
                                                $debit = $transaction->debit_amt;
                                                $credit = 0;
                                                $runningBalance += $normalBalance === 'debit' ? $debit : -$debit;
                                            } else {
                                                $debit = 0;
                                                $credit = $transaction->credit_amt;
                                                $runningBalance += $normalBalance === 'credit' ? $credit : -$credit;
                                            }
                                            $totalDebit += $debit;
                                            $totalCredit += $credit;
                                        @endphp
                                        <tr>
                                            <td>{{ $transaction->transaction_date }}</td>
                                            <td>{{ $transaction->payment_code }}</td>
                                            <td>{{ $transaction->transaction_type }}</td>
                                            <td>
                                                @if ($transaction->debit_account_id == $data['account']->id)
                                                    {{ $transaction->creditAccount->account_name ?? 'N/A' }}
                                                @else
                                                    {{ $transaction->debitAccount->account_name ?? 'N/A' }}
                                                @endif
                                                @if ($transaction->note)
                                                    <div class="text-muted small">{{ $transaction->note }}</div>
                                                @endif
                                            </td>
                                            <td>{{ number_format($debit, 2) }}</td>
                                            <td>{{ number_format($credit, 2) }}</td>
                                            <td>{{ (($normalBalance === 'debit' && $runningBalance >= 0) || ($normalBalance === 'credit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                            <td>{{ (($normalBalance === 'credit' && $runningBalance >= 0) || ($normalBalance === 'debit' && $runningBalance < 0)) ? number_format(abs($runningBalance), 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <th colspan="4">Total</th>
                                        <th>{{ number_format($totalDebit, 2) }}</th>
                                        <th>{{ number_format($totalCredit, 2) }}</th>
                                        <th colspan="2">
                                            @php
                                                $netTotal = $runningBalance;
                                            @endphp
                                            @if (($normalBalance === 'debit' && $netTotal >= 0) || ($normalBalance === 'credit' && $netTotal < 0))
                                                <span>Debit: {{ number_format(abs($netTotal), 2) }}</span>
                                            @else
                                                <span>Credit: {{ number_format(abs($netTotal), 2) }}</span>
                                            @endif
                                        </th>
                                    </tr>
                                </tbody>
                            </table>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
