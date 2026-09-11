@extends('backend.master')

@section('title')
    FB Marketing Profitability
@endsection

@section('content')
    @php
        $money = function ($value, $currency = null) {
            return ($currency ? $currency . ' ' : '') . number_format((float) $value, 2);
        };
        $label = fn($value) => ucwords(str_replace('_', ' ', (string) $value));
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Profitability</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Profitability</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-outline-primary" href="{{ route('fbMarketing.attribution-reports.index') }}">Attribution Reports</a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @foreach ($report['warnings'] as $warning)
                <div class="alert alert-{{ $warning['type'] }}">{{ $warning['message'] }}</div>
            @endforeach

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('fbMarketing.profitability.index') }}">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-3">
                                <label for="from_date">Date from</label>
                                <input class="form-control" id="from_date" type="date" name="from_date" value="{{ $report['filters']['from_date'] }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="to_date">Date to</label>
                                <input class="form-control" id="to_date" type="date" name="to_date" value="{{ $report['filters']['to_date'] }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="campaign_id">Campaign</label>
                                <select class="form-control" id="campaign_id" name="campaign_id">
                                    <option value="">All campaigns</option>
                                    @foreach ($report['campaign_options'] as $option)
                                        <option value="{{ $option['id'] }}" {{ (int) $report['filters']['campaign_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="ad_set_id">Ad set</label>
                                <select class="form-control" id="ad_set_id" name="ad_set_id">
                                    <option value="">All ad sets</option>
                                    @foreach ($report['ad_set_options'] as $option)
                                        <option value="{{ $option['id'] }}" {{ (int) $report['filters']['ad_set_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="ad_id">Ad</label>
                                <select class="form-control" id="ad_id" name="ad_id">
                                    <option value="">All ads</option>
                                    @foreach ($report['ad_options'] as $option)
                                        <option value="{{ $option['id'] }}" {{ (int) $report['filters']['ad_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="profit_state">Profit state</label>
                                <select class="form-control" id="profit_state" name="profit_state">
                                    <option value="">All states</option>
                                    @foreach ($report['profit_state_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['profit_state'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button class="btn btn-primary btn-block" type="submit">Apply filters</button>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a class="btn btn-outline-secondary btn-block" href="{{ route('fbMarketing.profitability.index') }}">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Meta spend', $money($report['summary']['meta_spend'], $report['summary']['currency'])],
                    ['Local adjustments', $money($report['summary']['local_cost_adjustments'], $report['summary']['currency'])],
                    ['Marketing cost', $money($report['summary']['total_marketing_cost'], $report['summary']['currency'])],
                    ['Attributed revenue', $money($report['summary']['attributed_revenue'], $report['summary']['currency'])],
                    ['Purchase cost', $money($report['summary']['purchase_cost'], $report['summary']['currency'])],
                    ['ERP contribution profit', $money($report['summary']['erp_contribution_profit'], $report['summary']['currency'])],
                    ['Ad-adjusted profit', $money($report['summary']['ad_adjusted_contribution_profit'], $report['summary']['currency'])],
                    ['Break-even gap', $money($report['summary']['break_even_gap'], $report['summary']['currency'])],
                    ['ROAS', number_format($report['summary']['roas'], 4)],
                    ['Adjusted margin', number_format($report['summary']['ad_adjusted_margin_percent'], 2) . '%'],
                    ['Attributed orders', number_format($report['summary']['attributed_orders'])],
                    ['Missing cost rows', number_format($report['summary']['missing_cost_count'])],
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

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Profit state breakdown</h5>
                            @if (empty($report['profit_breakdown']))
                                <p class="text-muted mb-0">No profitability row available.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead><tr><th>State</th><th>Rows</th><th>Revenue</th><th>Adjusted profit</th></tr></thead>
                                        <tbody>
                                            @foreach ($report['profit_breakdown'] as $row)
                                                <tr>
                                                    <td>{{ $row['label'] }}</td>
                                                    <td>{{ $row['row_count'] }}</td>
                                                    <td>{{ $money($row['revenue'], $report['summary']['currency']) }}</td>
                                                    <td>{{ $money($row['ad_adjusted_contribution_profit'], $report['summary']['currency']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Data freshness</h5>
                            <p class="text-muted">Report render uses application-local database rows only.</p>
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Latest snapshot date</dt><dd class="col-sm-6">{{ $report['freshness']['latest_snapshot_date'] ?: 'Unavailable' }}</dd>
                                <dt class="col-sm-6">Latest fetched at</dt><dd class="col-sm-6">{{ $report['freshness']['latest_fetched_at'] ?: 'Unavailable' }}</dd>
                                <dt class="col-sm-6">Latest ERP snapshot</dt><dd class="col-sm-6">{{ $report['freshness']['latest_order_snapshot_at'] ?: 'Unavailable' }}</dd>
                                <dt class="col-sm-6">Latest adjustment date</dt><dd class="col-sm-6">{{ $report['freshness']['latest_adjustment_date'] ?: 'Unavailable' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            @if ($canManageAdjustments && $report['schema_ready'])
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Add local campaign cost adjustment</h5>
                        <form method="POST" action="{{ route('fbMarketing.profitability.cost-adjustments.store', request()->query()) }}">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-2 mb-3">
                                    <label for="adjustment_effective_date">Effective date</label>
                                    <input class="form-control" id="adjustment_effective_date" type="date" name="effective_date" value="{{ $report['filters']['to_date'] }}" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="adjustment_campaign_id">Campaign</label>
                                    <select class="form-control" id="adjustment_campaign_id" name="campaign_id">
                                        <option value="">Unassigned</option>
                                        @foreach ($report['campaign_options'] as $option)
                                            <option value="{{ $option['id'] }}" {{ (int) $report['filters']['campaign_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="adjustment_ad_set_id">Ad set</label>
                                    <select class="form-control" id="adjustment_ad_set_id" name="ad_set_id">
                                        <option value="">Unassigned</option>
                                        @foreach ($report['ad_set_options'] as $option)
                                            <option value="{{ $option['id'] }}" {{ (int) $report['filters']['ad_set_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="adjustment_ad_id">Ad</label>
                                    <select class="form-control" id="adjustment_ad_id" name="ad_id">
                                        <option value="">Unassigned</option>
                                        @foreach ($report['ad_options'] as $option)
                                            <option value="{{ $option['id'] }}" {{ (int) $report['filters']['ad_id'] === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="adjustment_cost_type">Cost type</label>
                                    <select class="form-control" id="adjustment_cost_type" name="cost_type">
                                        @foreach ($report['cost_type_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="adjustment_label">Label</label>
                                    <input class="form-control" id="adjustment_label" type="text" name="label" maxlength="160" required>
                                </div>
                                <div class="col-md-1 mb-3">
                                    <label for="adjustment_currency">Currency</label>
                                    <input class="form-control" id="adjustment_currency" type="text" name="currency" maxlength="3" value="{{ $report['summary']['currency'] }}" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="adjustment_status">Status</label>
                                    <select class="form-control" id="adjustment_status" name="status">
                                        <option value="approved">Approved</option>
                                        <option value="pending">Pending</option>
                                        <option value="void">Void</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="base_amount">Base</label>
                                    <input class="form-control" id="base_amount" type="number" name="base_amount" min="0" step="0.01" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="vat_amount">VAT</label>
                                    <input class="form-control" id="vat_amount" type="number" name="vat_amount" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="tax_amount">Tax</label>
                                    <input class="form-control" id="tax_amount" type="number" name="tax_amount" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="service_charge_amount">Service charge</label>
                                    <input class="form-control" id="service_charge_amount" type="number" name="service_charge_amount" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="safe_note">Safe note</label>
                                    <input class="form-control" id="safe_note" type="text" name="safe_note" maxlength="500">
                                </div>
                                <div class="col-md-1 mb-3">
                                    <button class="btn btn-primary btn-block" type="submit">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif (!$canManageAdjustments)
                <div class="alert alert-light border">Your role does not have <code>fb_marketing_profitability_adjustment_manage.create</code>.</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Campaign profitability</h5>
                    <p class="text-muted">Spend comes from stored ad-level Insights snapshots. ERP contribution profit uses confirmed attributed ERP sales minus local product purchase cost. Ad-adjusted contribution profit subtracts Meta ad spend and approved local campaign cost adjustments.</p>
                    @if (empty($report['rows']))
                        <p class="text-muted mb-0">No profitability row matched the current filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Ad set / ad</th>
                                        <th>Revenue</th>
                                        <th>Purchase cost</th>
                                        <th>Meta spend</th>
                                        <th>Local cost</th>
                                        <th>ERP profit</th>
                                        <th>Adjusted profit</th>
                                        <th>Break-even gap</th>
                                        <th>ROAS</th>
                                        <th>State</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['campaign_name'] }}<br><small class="text-muted">{{ $row['latest_snapshot_date'] ?: 'No spend snapshot' }}</small></td>
                                            <td>{{ $row['ad_set_name'] }}<br><small class="text-muted">{{ $row['ad_name'] }}</small></td>
                                            <td>{{ $money($row['attributed_revenue'], $row['currency']) }}<br><small class="text-muted">{{ $row['attributed_orders'] }} order(s)</small></td>
                                            <td>{{ $money($row['purchase_cost'], $row['currency']) }}<br><small class="text-muted">{{ $row['missing_cost_count'] }} missing</small></td>
                                            <td>{{ $money($row['meta_spend'], $row['currency']) }}</td>
                                            <td>{{ $money($row['local_cost_adjustments'], $row['currency']) }}<br><small class="text-muted">{{ $row['adjustment_count'] }} row(s), VAT/tax/service {{ $money($row['vat_tax_service_total'], $row['currency']) }}</small></td>
                                            <td>{{ $money($row['erp_contribution_profit'], $row['currency']) }}<br><small class="text-muted">{{ number_format($row['contribution_margin_percent'], 2) }}%</small></td>
                                            <td>{{ $money($row['ad_adjusted_contribution_profit'], $row['currency']) }}<br><small class="text-muted">{{ number_format($row['ad_adjusted_margin_percent'], 2) }}%</small></td>
                                            <td>{{ $money($row['break_even_gap'], $row['currency']) }}<br><small class="text-muted">Need {{ $money($row['break_even_revenue'], $row['currency']) }}</small></td>
                                            <td>{{ number_format($row['roas'], 4) }}<br><small class="text-muted">CPA {{ $money($row['cost_per_attributed_order'], $row['currency']) }}</small></td>
                                            <td><span class="badge badge-{{ $row['profit_state'] === 'profitable' ? 'success' : 'warning' }}">{{ $label($row['profit_state']) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Approved local cost adjustments</h5>
                    @if (empty($report['adjustments']))
                        <p class="text-muted mb-0">No approved adjustment matched this range.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Date</th><th>Campaign</th><th>Ad set / ad</th><th>Type</th><th>Label</th><th>Base</th><th>VAT</th><th>Tax</th><th>Service</th><th>Total</th></tr></thead>
                                <tbody>
                                    @foreach ($report['adjustments'] as $row)
                                        <tr>
                                            <td>{{ $row['effective_date'] }}</td>
                                            <td>{{ $row['campaign_name'] }}</td>
                                            <td>{{ $row['ad_set_name'] }}<br><small class="text-muted">{{ $row['ad_name'] }}</small></td>
                                            <td>{{ $label($row['cost_type']) }}</td>
                                            <td>{{ $row['label'] }}</td>
                                            <td>{{ $money($row['base_amount'], $row['currency']) }}</td>
                                            <td>{{ $money($row['vat_amount'], $row['currency']) }}</td>
                                            <td>{{ $money($row['tax_amount'], $row['currency']) }}</td>
                                            <td>{{ $money($row['service_charge_amount'], $row['currency']) }}</td>
                                            <td>{{ $money($row['total_amount'], $row['currency']) }}</td>
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
