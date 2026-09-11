@extends('backend.master')

@section('page_title')
    Customer Due
@endsection

@section('page_heading')
    Customer Due
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
                    <h4 class="card-title mb-3">Customer Due Summary</h4>

                    <form method="GET" action="{{ route('ledger.customer_due') }}" class="mb-3">
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
                        $rows = collect($customerDue['rows'] ?? []);
                        $totals = $customerDue['totals'] ?? [];
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
                                    <th>Customer</th>
                                    <th class="text-right">Total Sales / Receivable Debit</th>
                                    <th class="text-right">Total Received / Receivable Credit</th>
                                    <th class="text-right">Current Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row['customer']->name ?? '-' }}</td>
                                        <td class="text-right">৳ {{ number_format($row['receivable_debit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['received_credit'] ?? 0, 2) }}</td>
                                        <td class="text-right">৳ {{ number_format($row['current_due'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No customer due found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="2" class="text-right">Total</td>
                                    <td class="text-right">৳ {{ number_format($totals['receivable_debit'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳ {{ number_format($totals['received_credit'] ?? 0, 2) }}</td>
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
