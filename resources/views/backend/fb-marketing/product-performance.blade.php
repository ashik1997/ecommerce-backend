@extends('backend.master')

@section('title')
    FB Marketing Product Performance
@endsection

@section('content')
    @php
        $money = fn($value) => number_format((float) $value, 2);
        $qty = fn($value) => number_format((float) $value, 3);
        $label = fn($value) => ucwords(str_replace('_', ' ', (string) $value));
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Product Performance</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Product Performance</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-outline-primary" href="{{ route('fbMarketing.products-catalog.index') }}">Products &amp; Catalog</a>
                    </div>
                </div>
            </div>

            @foreach ($report['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('fbMarketing.product-performance.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-3">
                                <label for="from_date">Date from</label>
                                <input class="form-control" id="from_date" type="date" name="from_date" value="{{ $report['filters']['from_date'] }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="to_date">Date to</label>
                                <input class="form-control" id="to_date" type="date" name="to_date" value="{{ $report['filters']['to_date'] }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="product_id">Product</label>
                                <select class="form-control" id="product_id" name="product_id">
                                    <option value="">All products</option>
                                    @foreach ($report['product_options'] as $product)
                                        <option value="{{ $product['id'] }}" {{ (int) $report['filters']['product_id'] === (int) $product['id'] ? 'selected' : '' }}>{{ $product['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="stock_risk">Stock risk</label>
                                <select class="form-control" id="stock_risk" name="stock_risk">
                                    <option value="">All risk states</option>
                                    @foreach ($report['stock_risk_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['stock_risk'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="catalog_status">Catalog</label>
                                <select class="form-control" id="catalog_status" name="catalog_status">
                                    <option value="">All catalog states</option>
                                    @foreach ($report['catalog_status_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['catalog_status'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1 mb-3">
                                <button class="btn btn-primary btn-block" type="submit">Apply</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Products', number_format($report['summary']['product_count'])],
                    ['Attributed orders', number_format($report['summary']['attributed_order_count'])],
                    ['Quantity sold', $qty($report['summary']['quantity_sold'])],
                    ['Revenue', $money($report['summary']['revenue'])],
                    ['Purchase cost', $money($report['summary']['purchase_cost'])],
                    ['Gross profit', $money($report['summary']['gross_profit'])],
                    ['Gross margin', number_format($report['summary']['gross_margin_percent'], 2) . '%'],
                    ['Risk products', number_format($report['summary']['risk_product_count'])],
                ] as $card)
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <p class="text-muted mb-1">{{ $card[0] }}</p>
                                <h4 class="mb-0">{{ $card[1] }}</h4>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Stock risk breakdown</h5>
                    @if (empty($report['risk_breakdown']))
                        <p class="text-muted mb-0">No product risk row available.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Risk</th><th>Products</th><th>Revenue</th></tr></thead>
                                <tbody>
                                    @foreach ($report['risk_breakdown'] as $row)
                                        <tr><td>{{ $row['label'] }}</td><td>{{ $row['product_count'] }}</td><td>{{ $money($row['revenue']) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Product-level attributed sales, profit and stock risk</h5>
                    <p class="text-muted">Rows use FBM-15 immutable item snapshots, local ERP product cost/stock fields and optional FBM-11 catalog mappings only.</p>
                    @if (empty($report['rows']))
                        <p class="text-muted mb-0">No product row matched the current filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Catalog</th>
                                        <th>Stock risk</th>
                                        <th>Stock</th>
                                        <th>Qty sold</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                        <th>Purchase cost</th>
                                        <th>Gross profit</th>
                                        <th>Margin</th>
                                        <th>Returns / cancellations</th>
                                        <th>Cost source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['rows'] as $row)
                                        <tr>
                                            <td>#{{ $row['product_id'] }} · {{ $row['product_name'] }}<br><small class="text-muted">{{ $row['sku'] ?: 'No SKU/code' }}</small></td>
                                            <td>{{ $label($row['catalog_status']) }}</td>
                                            <td><span class="badge badge-{{ in_array($row['stock_risk'], ['healthy','no_sales'], true) ? 'success' : 'warning' }}">{{ $label($row['stock_risk']) }}</span></td>
                                            <td>{{ $qty($row['stock_on_hand']) }}<br><small class="text-muted">Low: {{ $qty($row['low_stock_threshold']) }}</small></td>
                                            <td>{{ $qty($row['confirmed_quantity']) }}</td>
                                            <td>{{ $row['attributed_order_count'] }}</td>
                                            <td>{{ $money($row['confirmed_revenue']) }}</td>
                                            <td>{{ $money($row['purchase_cost']) }}</td>
                                            <td>{{ $money($row['gross_profit']) }}</td>
                                            <td>{{ number_format($row['gross_margin_percent'], 2) }}%</td>
                                            <td>{{ $qty($row['returned_quantity']) }} / {{ $qty($row['cancelled_quantity']) }}</td>
                                            <td>{{ $label($row['cost_source']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
