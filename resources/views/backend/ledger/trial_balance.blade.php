@extends('backend.master')

@section('page_title')
    Trial Balance
@endsection

@section('page_heading')
    Trial Balance
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
                    <h4 class="card-title mb-3">Trial Balance</h4>

                    <form method="GET" action="{{ route('ledger.trial_balance') }}" class="mb-3">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                        value="{{ request('start_date', $startDate ?? now('Asia/Dhaka')->subDays(30)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-lg-3">
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
                        $rows = collect($trialBalance['rows'] ?? []);
                        $totals = $trialBalance['totals'] ?? [];
                    @endphp

                    <div class="mb-3">
                        <strong>Period:</strong> {{ $startDate ?? '' }} to {{ $endDate ?? '' }}
                        @if (request('store_id'))
                            <span class="ml-3"><strong>Store ID:</strong> {{ request('store_id') }}</span>
                        @endif
                    </div>

                    @if (!($totals['is_matched'] ?? false))
                        <div class="alert alert-warning">
                            Trial Balance is not matched. Please check journal entries and account mapping.
                        </div>
                    @else
                        <div class="alert alert-success">
                            Trial Balance is matched.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">SL</th>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Account Type</th>
                                    <th>Normal Balance</th>
                                    <th class="text-right">Debit Total</th>
                                    <th class="text-right">Credit Total</th>
                                    <th class="text-right">Debit Balance</th>
                                    <th class="text-right">Credit Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $account = $row['account'];
                                        $normalBalance = $account->normal_balance
                                            ?: (in_array($account->account_type, ['asset', 'expense']) ? 'debit' : 'credit');
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $account->sort_code ?? '-' }}</td>
                                        <td>{{ $account->account_name ?? '-' }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $account->account_type ?? '-')) }}</td>
                                        <td>{{ ucfirst($normalBalance) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['debit_total'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['credit_total'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['debit_balance'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['credit_balance'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No account data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="5" class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($totals['debit_total'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['credit_total'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['debit_balance'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['credit_balance'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
