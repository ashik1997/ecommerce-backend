@extends('backend.master')

@section('content')
    @include('backend.fixed_asset._style')
    @include('backend.fixed_asset.partials.nav')

    <div class="fa-page-header">
        <div>
            <h3>Fixed Asset Accounting Ledger</h3>
            <p>Capitalization, depreciation, maintenance, disposal and reversal journal rows.</p>
        </div>
        <a href="{{ route('fixed-assets.accounting.ledger.export', request()->query()) }}" class="btn btn-success">Export CSV</a>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="fa-card">
                <div class="text-muted">Total Rows</div>
                <h4>{{ number_format($totals['rows'] ?? 0) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="fa-card">
                <div class="text-muted">Total Debit</div>
                <h4>{{ number_format($totals['debit'] ?? 0, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="fa-card">
                <div class="text-muted">Total Credit</div>
                <h4>{{ number_format($totals['credit'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="fa-card mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label>Event Type</label>
                <input type="text" name="event_type" value="{{ request('event_type') }}" class="form-control" placeholder="fixed_asset_...">
            </div>
            <div class="col-md-2">
                <label>Asset ID</label>
                <input type="number" name="asset_id" value="{{ request('asset_id') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label>Source Type</label>
                <input type="text" name="source_type" value="{{ request('source_type') }}" class="form-control" placeholder="depreciation_run">
            </div>
            <div class="col-md-2">
                <label>Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label>Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('fixed-assets.accounting.ledger') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>

    <div class="fa-card mb-3">
        <h5>Event Summary</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Event</th><th>Rows</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse($summary as $row)
                        <tr>
                            <td>{{ $row->event_type }}</td>
                            <td>{{ number_format($row->total_rows) }}</td>
                            <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No fixed asset accounting rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fa-card">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Debit Account</th>
                        <th>Credit Account</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th>Ref</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date }}</td>
                            <td>{{ $transaction->event_type }}</td>
                            <td>{{ optional($transaction->debitAccount)->account_name ?? $transaction->debit_account_id }}</td>
                            <td>{{ optional($transaction->creditAccount)->account_name ?? $transaction->credit_account_id }}</td>
                            <td class="text-end">{{ number_format($transaction->debit_amt, 2) }}</td>
                            <td class="text-end">{{ number_format($transaction->credit_amt, 2) }}</td>
                            <td>
                                Asset: {{ $transaction->ref_fixed_asset_id ?? '-' }}<br>
                                {{ $transaction->ref_fixed_asset_source_type ?? '-' }} #{{ $transaction->ref_fixed_asset_source_id ?? '-' }}
                            </td>
                            <td>{{ $transaction->note }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No ledger entries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $transactions->links() }}
    </div>
@endsection
