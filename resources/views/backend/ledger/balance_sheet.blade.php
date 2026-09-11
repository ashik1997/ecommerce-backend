@extends('backend.master')

@section('page_title', 'Balance Sheet')

@section('page_heading', 'Balance Sheet')

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
                    <h4 class="card-title mb-3">Balance Sheet</h4>

                    <form method="GET" action="{{ route('ledger.balance_sheet') }}" class="mb-4">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="end_date">As Of Date</label>
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
                        $sections = [
                            'Assets' => $balanceSheet['assets'] ?? [],
                            'Liabilities' => $balanceSheet['liabilities'] ?? [],
                            'Equity' => $balanceSheet['equity'] ?? [],
                        ];
                    @endphp

                    @foreach ($sections as $sectionName => $rows)
                        <h5>{{ $sectionName }}</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $row['account']->account_code }}</td>
                                        <td>{{ $row['account']->account_name }}</td>
                                        <td class="text-right">৳ {{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No {{ strtolower($sectionName) }} balance found.</td>
                                    </tr>
                                @endforelse

                                @if ($sectionName === 'Equity')
                                    <tr>
                                        <td></td>
                                        <td>Current Profit / Loss</td>
                                        <td class="text-right">৳ {{ number_format($balanceSheet['net_profit'] ?? 0, 2) }}</td>
                                    </tr>
                                @endif

                                <tr class="table-secondary font-weight-bold">
                                    <th colspan="2">Total {{ $sectionName }}</th>
                                    <th class="text-right">
                                        @if ($sectionName === 'Assets')
                                            ৳ {{ number_format($balanceSheet['total_assets'] ?? 0, 2) }}
                                        @elseif ($sectionName === 'Liabilities')
                                            ৳ {{ number_format($balanceSheet['total_liabilities'] ?? 0, 2) }}
                                        @else
                                            ৳ {{ number_format($balanceSheet['total_equity'] ?? 0, 2) }}
                                        @endif
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    @endforeach

                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Total Liabilities + Equity</th>
                                <td class="text-right">৳ {{ number_format($balanceSheet['total_liabilities_and_equity'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Difference</th>
                                <td class="text-right">
                                    @php $difference = (float) ($balanceSheet['difference'] ?? 0); @endphp
                                    @if (abs($difference) < 0.01)
                                        <span class="text-success font-weight-bold">Balanced</span>
                                    @else
                                        <span class="text-danger font-weight-bold">Unbalanced: ৳ {{ number_format($difference, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
