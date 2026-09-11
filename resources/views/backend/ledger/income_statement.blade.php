@extends('backend.master')

@section('page_title')
    Profit & Loss
@endsection

@section('page_heading')
    Profit & Loss / Income Statement
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
                    <h4 class="card-title mb-3">Profit & Loss / Income Statement</h4>

                    <form method="GET" action="{{ route('ledger.income_statement') }}" class="mb-3">
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

                    <h5 class="text-primary">Revenue</h5>
                    @include('backend.ledger.partials.profit_loss_rows', [
                        'rows' => $incomeStatement['revenueRows'] ?? [],
                        'totalLabel' => 'Total Revenue',
                        'totalAmount' => $incomeStatement['totalRevenue'] ?? 0,
                    ])

                    <h5 class="text-warning mt-4">Cost of Goods Sold</h5>
                    @include('backend.ledger.partials.profit_loss_rows', [
                        'rows' => $incomeStatement['cogsRows'] ?? [],
                        'totalLabel' => 'Total COGS',
                        'totalAmount' => $incomeStatement['totalCogs'] ?? 0,
                    ])

                    <table class="table table-bordered">
                        <tbody>
                            <tr class="font-weight-bold">
                                <td>Gross Profit</td>
                                <td class="text-right">৳ {{ number_format($incomeStatement['grossProfit'] ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <h5 class="text-danger mt-4">Operating Expenses</h5>
                    @include('backend.ledger.partials.profit_loss_rows', [
                        'rows' => $incomeStatement['operatingExpenseRows'] ?? [],
                        'totalLabel' => 'Total Operating Expense',
                        'totalAmount' => $incomeStatement['totalOperatingExpense'] ?? 0,
                    ])

                    @php $netProfit = (float) ($incomeStatement['netProfit'] ?? 0); @endphp
                    <table class="table table-bordered">
                        <tbody>
                            <tr class="font-weight-bold">
                                <td>{{ $netProfit >= 0 ? 'Net Profit' : 'Net Loss' }}</td>
                                <td class="text-right {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳ {{ number_format(abs($netProfit), 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
