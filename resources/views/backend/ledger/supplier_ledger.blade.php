@extends('backend.master')

@section('page_title')
    Supplier Ledger
@endsection

@section('page_heading')
    Supplier Ledger
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
                    <h4 class="card-title mb-3">Supplier Ledger</h4>

                    <form method="GET" action="{{ route('ledger.supplier_ledger') }}" class="mb-3">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="supplier_id">Supplier</label>
                                    <select id="supplier_id" name="supplier_id" class="form-control">
                                        <option value="">All Suppliers</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
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
                        $rows = collect($supplierLedger['rows'] ?? []);
                    @endphp

                    <div class="mb-3">
                        <strong>Period:</strong> {{ $startDate ?? '' }} to {{ $endDate ?? '' }}
                        @if ($selectedSupplier)
                            <span class="ml-3"><strong>Supplier:</strong> {{ $selectedSupplier->name }}</span>
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
                                    <th>Supplier</th>
                                    <th>Payment Code</th>
                                    <th>Type</th>
                                    <th>Payable Account</th>
                                    <th>Opposite Account</th>
                                    <th class="text-right">Debit / Paid</th>
                                    <th class="text-right">Credit / Payable</th>
                                    <th class="text-right">Balance Due</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="font-weight-bold">
                                    <td colspan="9" class="text-right">Opening Balance</td>
                                    <td class="text-right">৳ {{ number_format($supplierLedger['opening_balance'] ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>

                                @forelse ($rows as $row)
                                    @php
                                        $transaction = $row['transaction'];
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $transaction->transaction_date }}</td>
                                        <td>{{ $row['supplier_name'] ?: '-' }}</td>
                                        <td>{{ $transaction->payment_code ?? '-' }}</td>
                                        <td>
                                            {{ ucwords(str_replace('_', ' ', $transaction->transaction_type ?? '-')) }}
                                            @if ($transaction->event_type)
                                                <br><small class="text-muted">{{ $transaction->event_type }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $row['payable_account_name'] ?: '-' }}</td>
                                        <td>{{ $row['opposite_account_name'] ?: '-' }}</td>
                                        <td class="text-right">৳ {{ number_format($row['debit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['credit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['running_balance'] ?? 0, 2) }}</td>
                                        <td>{{ $transaction->note ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No supplier payable transaction found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="7" class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($supplierLedger['total_debit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($supplierLedger['total_credit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($supplierLedger['closing_balance'] ?? 0, 2) }}</td>
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
