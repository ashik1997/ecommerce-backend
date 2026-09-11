@extends('backend.master')

@section('title')
    FB Marketing Boosting Jobs
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
                        <h3 class="page-title mb-1">Boosting Jobs</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">Boosting Jobs</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-outline-primary" href="{{ route('fbMarketing.profitability.index') }}">Profitability</a>
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
                    <form method="GET" action="{{ route('fbMarketing.boosting-jobs.index') }}">
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
                                <label for="mode">Mode</label>
                                <select class="form-control" id="mode" name="mode">
                                    <option value="">All modes</option>
                                    @foreach ($report['mode_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['mode'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All statuses</option>
                                    @foreach ($report['status_options'] as $value => $text)
                                        <option value="{{ $value }}" {{ $report['filters']['status'] === $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">All customers</option>
                                    @foreach ($report['customer_options'] as $customer)
                                        <option value="{{ $customer['id'] }}" {{ (int) $report['filters']['customer_id'] === (int) $customer['id'] ? 'selected' : '' }}>{{ $customer['name'] }}</option>
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
                    ['Jobs', number_format($report['summary']['job_count'])],
                    ['Client jobs', number_format($report['summary']['client_job_count'])],
                    ['Own-store jobs', number_format($report['summary']['own_store_job_count'])],
                    ['Planned budget', $money($report['summary']['planned_budget'], $report['summary']['currency'])],
                    ['Actual spend', $money($report['summary']['actual_spend'], $report['summary']['currency'])],
                    ['Service fee', $money($report['summary']['service_fee'], $report['summary']['currency'])],
                    ['Received', $money($report['summary']['received_amount'], $report['summary']['currency'])],
                    ['Balance', $money($report['summary']['balance'], $report['summary']['currency'])],
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

            @if ($canManageJobs && $report['schema_ready'])
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Create boosting job</h5>
                        <form method="POST" action="{{ route('fbMarketing.boosting-jobs.store', request()->query()) }}">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-3">
                                    <label for="title">Title</label>
                                    <input class="form-control" id="title" type="text" name="title" maxlength="180" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="job_mode">Mode</label>
                                    <select class="form-control" id="job_mode" name="mode">
                                        @foreach ($report['mode_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="job_customer_id">Customer</label>
                                    <select class="form-control" id="job_customer_id" name="customer_id">
                                        <option value="">No mapped customer</option>
                                        @foreach ($report['customer_options'] as $customer)
                                            <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="client_name">Client name</label>
                                    <input class="form-control" id="client_name" type="text" name="client_name" maxlength="180">
                                </div>
                                <div class="col-md-1 mb-3">
                                    <label for="currency">Currency</label>
                                    <input class="form-control" id="currency" type="text" name="currency" value="{{ $report['summary']['currency'] }}" maxlength="3" required>
                                </div>
                                <div class="col-md-1 mb-3">
                                    <label for="job_status">Status</label>
                                    <select class="form-control" id="job_status" name="status">
                                        @foreach ($report['status_options'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="planned_budget">Planned budget</label>
                                    <input class="form-control" id="planned_budget" type="number" name="planned_budget" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="service_fee">Service fee</label>
                                    <input class="form-control" id="service_fee" type="number" name="service_fee" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="start_date">Start date</label>
                                    <input class="form-control" id="start_date" type="date" name="start_date">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="end_date">End date</label>
                                    <input class="form-control" id="end_date" type="date" name="end_date">
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
            @endif

            @if (($canManageJobs || $canManageLedger) && $report['schema_ready'] && !empty($report['rows']))
                <div class="row">
                    @if ($canManageJobs)
                        <div class="col-lg-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Link campaign</h5>
                                    <form method="POST" action="{{ route('fbMarketing.boosting-jobs.campaigns.store', request()->query()) }}">
                                        @csrf
                                        @include('backend.fb-marketing._boosting-job-select', ['rows' => $report['rows']])
                                        <div class="form-group">
                                            <label>Campaign</label>
                                            <select class="form-control" name="campaign_id">
                                                <option value="">Unassigned</option>
                                                @foreach ($report['campaign_options'] as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Ad set</label>
                                            <select class="form-control" name="ad_set_id">
                                                <option value="">All ad sets</option>
                                                @foreach ($report['ad_set_options'] as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Ad</label>
                                            <select class="form-control" name="ad_id">
                                                <option value="">All ads</option>
                                                @foreach ($report['ad_options'] as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Allocation %</label>
                                            <input class="form-control" type="number" name="allocation_percent" min="0" max="100" step="0.01" value="100">
                                        </div>
                                        <button class="btn btn-outline-primary" type="submit">Link</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($canManageLedger)
                        <div class="col-lg-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Add payment</h5>
                                    <form method="POST" action="{{ route('fbMarketing.boosting-jobs.payments.store', request()->query()) }}">
                                        @csrf
                                        @include('backend.fb-marketing._boosting-job-select', ['rows' => $report['rows']])
                                        <div class="form-group"><label>Date</label><input class="form-control" type="date" name="payment_date" value="{{ $report['filters']['to_date'] }}" required></div>
                                        <div class="form-group">
                                            <label>Type</label>
                                            <select class="form-control" name="payment_type">
                                                @foreach ($report['payment_type_options'] as $value => $text)
                                                    <option value="{{ $value }}">{{ $text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group"><label>Currency</label><input class="form-control" type="text" name="currency" value="{{ $report['summary']['currency'] }}" maxlength="3" required></div>
                                        <div class="form-group"><label>Amount</label><input class="form-control" type="number" name="amount" min="0" step="0.01" required></div>
                                        <div class="form-group"><label>Method</label><input class="form-control" type="text" name="method" maxlength="80"></div>
                                        <div class="form-group"><label>Reference</label><input class="form-control" type="text" name="safe_reference" maxlength="180"></div>
                                        <input type="hidden" name="status" value="confirmed">
                                        <button class="btn btn-outline-primary" type="submit">Add</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Add cost</h5>
                                    <form method="POST" action="{{ route('fbMarketing.boosting-jobs.costs.store', request()->query()) }}">
                                        @csrf
                                        @include('backend.fb-marketing._boosting-job-select', ['rows' => $report['rows']])
                                        <div class="form-group"><label>Date</label><input class="form-control" type="date" name="cost_date" value="{{ $report['filters']['to_date'] }}" required></div>
                                        <div class="form-group">
                                            <label>Type</label>
                                            <select class="form-control" name="cost_type">
                                                @foreach ($report['cost_type_options'] as $value => $text)
                                                    <option value="{{ $value }}">{{ $text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group"><label>Label</label><input class="form-control" type="text" name="label" maxlength="160" required></div>
                                        <div class="form-group"><label>Currency</label><input class="form-control" type="text" name="currency" value="{{ $report['summary']['currency'] }}" maxlength="3" required></div>
                                        <div class="form-row">
                                            <div class="form-group col"><label>Base</label><input class="form-control" type="number" name="base_amount" min="0" step="0.01" required></div>
                                            <div class="form-group col"><label>VAT</label><input class="form-control" type="number" name="vat_amount" min="0" step="0.01" value="0"></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col"><label>Tax</label><input class="form-control" type="number" name="tax_amount" min="0" step="0.01" value="0"></div>
                                            <div class="form-group col"><label>Service</label><input class="form-control" type="number" name="service_charge_amount" min="0" step="0.01" value="0"></div>
                                        </div>
                                        <input type="hidden" name="status" value="approved">
                                        <button class="btn btn-outline-primary" type="submit">Add</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Boosting job ledger</h5>
                    <p class="text-muted">Rows are application-local job records. Actual spend is read from stored ad-level Insights snapshots for linked local campaign/ad rows only.</p>
                    @if (empty($report['rows']))
                        <p class="text-muted mb-0">No boosting job matched the current filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Job</th>
                                        <th>Mode / client</th>
                                        <th>Budget</th>
                                        <th>Actual spend</th>
                                        <th>Service / costs</th>
                                        <th>Received</th>
                                        <th>Balance</th>
                                        <th>Links</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['job_code'] }}<br><strong>{{ $row['title'] }}</strong><br><small class="text-muted">{{ $row['start_date'] ?: 'No start' }} - {{ $row['end_date'] ?: 'No end' }}</small></td>
                                            <td>{{ $label($row['mode']) }}<br><small class="text-muted">{{ $row['client_name'] }}</small></td>
                                            <td>{{ $money($row['planned_budget'], $row['currency']) }}</td>
                                            <td>{{ $money($row['actual_spend'], $row['currency']) }}</td>
                                            <td>Fee {{ $money($row['service_fee'], $row['currency']) }}<br><small class="text-muted">Costs {{ $money($row['local_cost'], $row['currency']) }}</small></td>
                                            <td>{{ $money($row['received_amount'], $row['currency']) }}<br><small class="text-muted">Refund {{ $money($row['refund_amount'], $row['currency']) }}</small></td>
                                            <td>{{ $money($row['balance'], $row['currency']) }}<br><small class="text-muted">Receivable {{ $money($row['receivable_total'], $row['currency']) }}</small></td>
                                            <td>
                                                {{ $row['campaign_link_count'] }} link(s)
                                                @foreach ($row['linked_campaigns'] as $link)
                                                    <br><small class="text-muted">{{ $link['campaign_name'] }} / {{ $link['ad_set_name'] }} / {{ $link['ad_name'] }} · {{ number_format($link['allocation_percent'], 2) }}%</small>
                                                @endforeach
                                            </td>
                                            <td><span class="badge badge-{{ $row['status'] === 'active' ? 'success' : 'secondary' }}">{{ $label($row['status']) }}</span></td>
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
