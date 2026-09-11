@extends('backend.master')

@section('title')
    FB Marketing Products & Catalog
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-1">Products & Catalog</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                        <li class="breadcrumb-item active">Products & Catalog</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a class="btn btn-outline-primary" href="{{ route('fbMarketing.configuration.index') }}">Configuration</a>
                </div>
            </div>
        </div>

        @include('backend.fb-marketing._status-card')

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger"><strong>The Products & Catalog request could not be completed.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @unless ($schemaReady)
            <div class="alert alert-warning"><strong>FBM-11 migration required.</strong> Apply the guarded catalog-mapping migration before using catalog products, product sets or local mapping overrides.</div>
        @endunless

        <div class="row">
            @foreach ([
                'Feed valid products' => $feedDiagnostics['feed_ready_products'],
                'Mapped catalog items' => $catalogSummary['automatic_mapping_count'] + $catalogSummary['manual_mapping_count'],
                'Unmatched items' => $catalogSummary['unmatched_count'],
                'Product sets' => $catalogSummary['product_set_count'],
            ] as $label => $count)
                <div class="col-lg-3 col-md-6 mb-3"><div class="border rounded p-3 h-100"><div class="text-muted small">{{ $label }}</div><div class="h4 mb-0">{{ number_format($count) }}</div></div></div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3">
                    @foreach (['feed-products' => 'Feed Products', 'product-mapping' => 'Product Mapping', 'catalog-sync' => 'Catalog Sync', 'feed-diagnostics' => 'Feed Diagnostics'] as $key => $label)
                        <li class="nav-item"><a class="nav-link {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('fbMarketing.products-catalog.index', ['tab' => $key]) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>

                @if ($activeTab === 'feed-products')
                    <div class="d-flex flex-wrap justify-content-between mb-3">
                        <div><h5 class="mb-1">Feed products</h5><p class="text-muted mb-0">XML feed and this table share one projection. Invalid selected products are excluded from XML output.</p></div>
                        <a class="btn btn-sm btn-outline-info" target="_blank" href="{{ $feedDiagnostics['feed_url'] }}">Open XML feed</a>
                    </div>
                    <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>ERP ID</th><th>Product</th><th>Feed status</th><th>Availability</th><th>Stock</th><th>Price</th><th>Diagnostics</th></tr></thead><tbody>
                    @forelse ($feedProducts as $product)
                        <tr><td>{{ $product['id'] }}</td><td>{{ $product['title'] ?: '—' }}</td><td><span class="badge badge-{{ $product['is_feed_ready'] ? 'success' : 'warning' }}">{{ $product['is_feed_ready'] ? 'Emitted' : 'Excluded' }}</span></td><td>{{ $product['availability'] }}</td><td>{{ number_format($product['stock_quantity'], 2) }}</td><td>{{ number_format($product['price_amount'], 2) }} {{ $product['currency'] }}</td><td>{{ implode(', ', $product['diagnostic_flags']) ?: 'None' }}</td></tr>
                    @empty <tr><td colspan="7" class="text-center text-muted">No opted-in ERP product found.</td></tr> @endforelse
                    </tbody></table></div>
                @elseif ($activeTab === 'product-mapping')
                    <h5 class="mb-1">Catalog item ↔ ERP product mapping</h5>
                    <p class="text-muted">Automatic match uses Meta retailer ID equal to ERP product ID. Manual overrides update the application database only; no Meta write request is sent.</p>
                    <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Catalog</th><th>Retailer ID</th><th>Catalog item</th><th>Mapping</th><th>ERP product</th><th>Diagnostics</th><th>Local override</th></tr></thead><tbody>
                    @forelse ($mappings as $mapping)
                        <tr><td>{{ $mapping['catalog_name'] ?: ('#' . $mapping['fbm_catalog_id']) }}</td><td>{{ $mapping['retailer_id'] ?: '—' }}</td><td>{{ $mapping['item_name'] ?: '—' }}</td><td><span class="badge badge-{{ in_array($mapping['mapping_status'], ['automatic','manual'], true) ? 'success' : 'warning' }}">{{ ucwords(str_replace('_', ' ', $mapping['mapping_status'])) }}</span></td><td>{{ $mapping['product_id'] ? ('#' . $mapping['product_id'] . ' · ' . ($mapping['product_name'] ?: 'Unnamed')) : '—' }}</td><td>{{ implode(', ', $mapping['diagnostic_flags']) ?: 'None' }}</td><td>
                            @if ($canManageMappings)
                                <form method="POST" action="{{ route('fbMarketing.products-catalog.mappings.update', $mapping['id']) }}">@csrf @method('PATCH')
                                    <input class="form-control form-control-sm mb-1" type="number" min="1" name="product_id" placeholder="ERP product ID; blank clears override" value="{{ $mapping['mapping_source'] === 'manual_override' ? $mapping['product_id'] : '' }}">
                                    <input class="form-control form-control-sm mb-1" maxlength="500" name="mapping_note" placeholder="Optional local note" value="{{ $mapping['mapping_note'] }}">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Save local mapping</button>
                                </form>
                            @else <small class="text-muted">Mapping permission required.</small> @endif
                        </td></tr>
                    @empty <tr><td colspan="7" class="text-center text-muted">Run a catalog refresh after selecting a discovered catalog.</td></tr> @endforelse
                    </tbody></table></div>
                @elseif ($activeTab === 'catalog-sync')
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div><h5 class="mb-1">Read-only catalog refresh</h5><p class="text-muted mb-0">Catalog products and product sets are mirrored locally. Provider IDs, raw payloads and raw URLs remain hidden.</p></div>
                    </div>
                    @if ($canViewConfiguration && $canRunCatalogSync && $manualCatalogSyncEnabled && $schemaReady)
                        @forelse ($connections as $connection)
                            <form class="d-inline-block mr-2 mb-3" method="POST" action="{{ route('fbMarketing.configuration.connections.refresh-catalog-now', $connection->id) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Refresh catalog mappings now (no queue) · {{ $connection->connection_name }}</button></form>
                        @empty <p class="text-muted">Select an available catalog for an active connection before running the manual refresh.</p> @endforelse
                    @elseif (!$canRunCatalogSync)
                        <p class="text-muted">Your role does not have the contextual catalog-sync permission.</p>
                    @endif
                    <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Catalog</th><th>Set name</th><th>Filter</th><th>Status</th><th>Last seen</th></tr></thead><tbody>
                    @forelse ($productSets as $set)<tr><td>{{ $set['catalog_name'] ?: ('#' . $set['fbm_catalog_id']) }}</td><td>{{ $set['set_name'] ?: '—' }}</td><td>{{ !empty($set['filter_summary']['configured']) ? 'Configured — hidden summary' : 'Not reported' }}</td><td>{{ $set['is_available'] ? 'Available' : 'Unavailable' }}</td><td>{{ $set['last_seen_at'] ?: '—' }}</td></tr>@empty <tr><td colspan="5" class="text-center text-muted">No product sets mirrored yet.</td></tr>@endforelse
                    </tbody></table></div>
                    <h6 class="mt-4">Catalog sync ledger</h6>
                    <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Created</th><th>Connection / catalog</th><th>Mode</th><th>Status</th><th>Items</th><th>Sets</th><th>Mapped / unmatched</th><th>Diagnostic</th></tr></thead><tbody>
                    @forelse ($syncRuns as $run)<tr><td>{{ $run['created_at'] ?: '—' }}</td><td>{{ $run['connection_name'] ?: ('#' . $run['fbm_connection_id']) }} / {{ $run['catalog_name'] ?: 'summary' }}</td><td>{{ ucwords(str_replace('_', ' ', $run['execution_mode'])) }}</td><td>{{ ucwords(str_replace('_', ' ', $run['status'])) }}</td><td>{{ $run['product_item_count'] }}</td><td>{{ $run['product_set_count'] }}</td><td>{{ $run['automatic_mapping_count'] + $run['manual_mapping_count'] }} / {{ $run['unmatched_count'] + $run['ambiguous_count'] }}</td><td>{{ $run['redacted_message'] ?: '—' }}</td></tr>@empty <tr><td colspan="8" class="text-center text-muted">No catalog sync ledger row recorded yet.</td></tr>@endforelse
                    </tbody></table></div>
                @else
                    <h5 class="mb-1">Feed diagnostics</h5><p class="text-muted">Valid feed rows reconcile with XML output. Stock and variant warnings do not silently change the established content IDs.</p>
                    <div class="row">@foreach ($feedDiagnostics['excluded_counts'] as $reason => $count)<div class="col-lg-3 col-md-6 mb-2"><div class="border rounded p-3 h-100"><div class="text-muted small">{{ ucwords(str_replace('_', ' ', $reason)) }}</div><div class="h5 mb-0">{{ number_format($count) }}</div></div></div>@endforeach</div>
                    @if ($feedDiagnostics['diagnostic_scan_truncated'])
                        <div class="alert alert-warning mt-3">Stock and variant warning totals scan the latest {{ number_format($feedDiagnostics['diagnostic_scan_limit']) }} opted-in products only. Use an offline audit for an exhaustive larger-catalog result.</div>
                    @endif
                    <div class="alert alert-info mt-3 mb-0">Variant products are currently exported at parent-product level. Variant-level feed expansion is deferred so existing retailer IDs do not change silently.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
