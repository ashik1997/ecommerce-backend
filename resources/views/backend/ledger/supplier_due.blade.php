@extends('backend.master')

@section('page_title')
    Supplier Due
@endsection

@section('page_heading')
    Supplier Due
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
                    <h4 class="card-title mb-3">Supplier Due Summary</h4>

                    <form method="GET" action="{{ route('ledger.supplier_due') }}" class="mb-3">
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
                                        value="{{ request('start_date', $startDate ?? '') }}">
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
                        $rows = collect($supplierDue['rows'] ?? []);
                        $totals = $supplierDue['totals'] ?? [];
                    @endphp

                    <div class="mb-3">
                        <strong>Period:</strong> {{ $startDate ?: 'Beginning' }} to {{ $endDate ?? '' }}
                        @if (request('store_id'))
                            <span class="ml-3"><strong>Store ID:</strong> {{ request('store_id') }}</span>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">SL</th>
                                    <th>Supplier</th>
                                    <th class="text-right">Total Purchase / Payable Credit</th>
                                    <th class="text-right">Total Paid / Payable Debit</th>
                                    <th class="text-right">Current Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row['supplier']->name ?? '-' }}</td>
                                        <td class="text-right">৳ {{ number_format($row['payable_credit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['paid_debit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['current_due'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No supplier due found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="2" class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($totals['payable_credit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['paid_debit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['current_due'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
