@extends('backend.master')

@section('page_title')
    Return Refund Dashboard
@endsection

@section('page_heading')
    Return Refund Dashboard
@endsection

@section('header_css')
    <style>
        .rr-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .rr-kpi {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
            height: 100%;
        }

        .rr-kpi .label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin-bottom: 8px;
        }

        .rr-kpi .value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .rr-kpi .sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        .rr-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            height: 100%;
        }

        .rr-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eef2f7;
            font-weight: 700;
        }

        .rr-table th {
            font-size: 12px;
            color: #475569;
            background: #f8fafc;
            white-space: nowrap;
        }

        .rr-table td {
            vertical-align: middle;
        }

        .rr-money {
            font-weight: 700;
            white-space: nowrap;
        }
    </style>
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card rr-card">
                <div class="card-body">
                    <form method="GET" class="rr-filter">
                        <div>
                            <label class="mb-1">From</label>
                            <input type="date" name="from" value="{{ $from }}" class="form-control">
                        </div>
                        <div>
                            <label class="mb-1">To</label>
                            <input type="date" name="to" value="{{ $to }}" class="form-control">
                        </div>
                        <button class="btn btn-primary" type="submit">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('ReturnRefundDashboard') }}" class="btn btn-light border">
                            <i class="fa fa-rotate-left"></i>
                        </a>
                        <a href="{{ route('ViewAllProductOrderReturns') }}" class="btn btn-outline-secondary ml-auto">
                            <i class="fa fa-list"></i> Return List
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="rr-kpi">
                <div class="label">Returned Value</div>
                <div class="value">৳{{ number_format($summary['returned_value'], 2) }}</div>
                <div class="sub">{{ number_format($summary['return_count']) }} returns · {{ number_format($summary['returned_qty']) }} qty</div>
                <div class="sub">Order ৳{{ number_format($summary['order_returned_value'], 2) }} · Manual ৳{{ number_format($summary['manual_returned_value'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="rr-kpi">
                <div class="label">Refund Paid</div>
                <div class="value text-success">৳{{ number_format($summary['refund_paid_in_range'], 2) }}</div>
                <div class="sub">Paid within selected refund date range</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="rr-kpi">
                <div class="label">Payable</div>
                <div class="value text-danger">৳{{ number_format($summary['payable_for_returns'], 2) }}</div>
                <div class="sub">Unpaid against returns in this range</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="rr-kpi">
                <div class="label">Pending Refunds</div>
                <div class="value">{{ number_format($summary['pending_refund_count']) }}</div>
                <div class="sub">{{ number_format($summary['order_return_count']) }} order · {{ number_format($summary['manual_return_count']) }} manual</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-3">
            <div class="card rr-card">
                <div class="card-header">Pending Refunds</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 rr-table">
                            <thead>
                                <tr>
                                    <th>Return</th>
                                    <th>Type</th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th class="text-right">Payable</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingReturns as $return)
                                    <tr>
                                        <td>{{ $return->code }}</td>
                                        <td><span class="badge badge-{{ $return->source === 'Manual' ? 'warning' : 'info' }}">{{ $return->source }}</span></td>
                                        <td>{{ $return->order_code }}</td>
                                        <td>{{ $return->customer_name }}</td>
                                        <td>{{ $return->date }}</td>
                                        <td class="text-right rr-money">৳{{ number_format($return->payable, 2) }}</td>
                                        <td class="text-right">
                                            <a href="{{ $return->url }}" class="btn btn-sm btn-outline-primary">Open</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No pending refunds found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-3">
            <div class="card rr-card">
                <div class="card-header">Refund By Payment Type</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 rr-table">
                            <thead>
                                <tr>
                                    <th>Payment Type</th>
                                    <th class="text-right">Count</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentBreakdown as $row)
                                    <tr>
                                        <td>{{ $row->payment_type }}</td>
                                        <td class="text-right">{{ number_format($row->refund_count) }}</td>
                                        <td class="text-right rr-money">৳{{ number_format($row->refund_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No refunds found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-3">
            <div class="card rr-card">
                <div class="card-header">Top Returned Products</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 rr-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $product)
                                    <tr>
                                        <td>{{ $product->product_name }}</td>
                                        <td class="text-right">{{ number_format($product->returned_qty) }}</td>
                                        <td class="text-right rr-money">৳{{ number_format($product->returned_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No returned products found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-3">
            <div class="card rr-card">
                <div class="card-header">Recent Refunds</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 rr-table">
                            <thead>
                                <tr>
                                    <th>Refund</th>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRefunds as $refund)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ShowProductOrderRefund', $refund->slug) }}" target="_blank">
                                                {{ $refund->refund_code }}
                                            </a>
                                        </td>
                                        <td>{{ $refund->refund_date }}</td>
                                        <td>{{ $refund->payment_type_snapshot ?? optional($refund->paymentType)->payment_type ?? 'N/A' }}</td>
                                        <td class="text-right rr-money">৳{{ number_format($refund->refund_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent refunds found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
