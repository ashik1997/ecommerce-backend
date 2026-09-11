@extends('backend.master')

@section('header_css')
    <style>
        .inventory-dashboard {
            --inv-border: #e7ebf0;
            --inv-text: #172033;
            --inv-muted: #6b7280;
            --inv-bg: #f6f8fb;
        }

        .inventory-filter {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 14px;
            padding: 16px;
            margin-bottom: 18px;
            background: #fff;
            border: 1px solid var(--inv-border);
            border-radius: 8px;
        }

        .filter-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 10px;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .metric-card,
        .panel-card {
            background: #fff;
            border: 1px solid var(--inv-border);
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(23, 32, 51, 0.05);
        }

        .metric-card {
            min-height: 118px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-label {
            color: var(--inv-muted);
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .metric-value {
            color: var(--inv-text);
            font-size: 24px;
            line-height: 1.15;
            font-weight: 800;
            word-break: break-word;
        }

        .metric-meta {
            color: var(--inv-muted);
            font-size: 12px;
        }

        .tone-primary { border-top: 3px solid #2563eb; }
        .tone-success { border-top: 3px solid #059669; }
        .tone-warning { border-top: 3px solid #d97706; }
        .tone-danger { border-top: 3px solid #dc2626; }
        .tone-info { border-top: 3px solid #0891b2; }
        .tone-dark { border-top: 3px solid #172033; }

        .pipeline-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin: 16px 0;
        }

        .pipeline-item {
            padding: 12px;
            background: #fff;
            border: 1px solid var(--inv-border);
            border-radius: 8px;
        }

        .panel-card {
            padding: 18px;
            height: 100%;
        }

        .panel-title {
            color: var(--inv-text);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .relation-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #eef1f5;
        }

        .relation-row:last-child {
            border-bottom: 0;
        }

        .chart-shell {
            height: 320px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            background: #eef2ff;
            color: #172033;
        }

        .loading-text {
            color: var(--inv-muted);
            font-size: 13px;
        }

        @media (max-width: 1199px) {
            .metric-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .pipeline-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 767px) {
            .inventory-filter { align-items: stretch; flex-direction: column; }
            .filter-controls { flex-direction: column; align-items: stretch; }
            .metric-grid,
            .pipeline-grid { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('page_title')
    Inventory Dashboard
@endsection

@section('page_heading')
    Inventory Overview
@endsection

@section('content')
    <div class="inventory-dashboard">
        <div class="inventory-filter">
            <div>
                <h4 class="mb-1">At a Glance</h4>
                <div class="text-muted">Inventory, purchase, return, supplier and customer relation in one view.</div>
            </div>

            <div class="filter-controls">
                <div>
                    <label class="mb-1">Period</label>
                    <select id="inventoryPreset" class="form-control">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last_week">Last Week</option>
                        <option value="this_month">This Month</option>
                        <option value="lifetime">Lifetime</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="custom-date d-none">
                    <label class="mb-1">Start Date</label>
                    <input id="inventoryStartDate" type="date" class="form-control">
                </div>
                <div class="custom-date d-none">
                    <label class="mb-1">End Date</label>
                    <input id="inventoryEndDate" type="date" class="form-control">
                </div>
                <button id="inventoryApplyFilter" class="btn btn-primary">
                    <i class="feather-filter mr-1"></i> Apply
                </button>
            </div>
        </div>

        <div id="overviewCards" class="metric-grid">
            <div class="loading-text">Loading overview...</div>
        </div>

        <div id="pipelineGrid" class="pipeline-grid"></div>

        <div class="row mt-3">
            <div class="col-lg-6 mb-3">
                <div class="panel-card">
                    <div class="panel-title">Supplier Relation</div>
                    <div id="supplierRelation" class="loading-text">Loading supplier relation...</div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="panel-card">
                    <div class="panel-title">Customer Relation</div>
                    <div id="customerRelation" class="loading-text">Loading customer relation...</div>
                </div>
            </div>
        </div>

        <div class="row mt-1">
            <div class="col-lg-12 mb-3">
                <div class="panel-card">
                    <div class="panel-title">Purchase Trend</div>
                    <div class="chart-shell">
                        <canvas id="purchaseTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-1">
            <div class="col-lg-8 mb-3">
                <div class="panel-card">
                    <div class="panel-title">Recent Received Purchases</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>PO</th>
                                    <th>Supplier</th>
                                    <th>Date</th>
                                    <th class="text-right">Subtotal</th>
                                    <th class="text-right">Other</th>
                                    <th class="text-right">Discount</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody id="recentPurchasesBody">
                                <tr><td colspan="7" class="text-muted">Loading purchases...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="panel-card">
                    <div class="panel-title">Recent Purchase Returns</div>
                    <div id="recentReturns" class="loading-text">Loading returns...</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        const inventoryApi = {
            overview: "{{ route('inventory.home.api.overview') }}",
            relationships: "{{ route('inventory.home.api.relationships') }}",
            trends: "{{ route('inventory.home.api.trends') }}",
            recent: "{{ route('inventory.home.api.recent') }}",
        };

        let purchaseTrendChart = null;
        const moneyFormatter = new Intl.NumberFormat('en-BD', {
            style: 'currency',
            currency: 'BDT',
            maximumFractionDigits: 0,
        });

        function filterQuery() {
            const preset = document.getElementById('inventoryPreset').value;
            const params = new URLSearchParams({ preset });

            if (preset === 'custom') {
                params.set('start_date', document.getElementById('inventoryStartDate').value);
                params.set('end_date', document.getElementById('inventoryEndDate').value);
            }

            return params.toString();
        }

        function formatMoney(value) {
            return moneyFormatter.format(Number(value || 0));
        }

        function formatValue(card) {
            if (card.format === 'status') {
                return `<span class="status-pill">${card.value.text}</span><div class="metric-meta mt-2">${formatMoney(card.value.amount)}</div>`;
            }

            return card.format === 'money' ? formatMoney(card.value) : Number(card.value || 0).toLocaleString();
        }

        async function fetchJson(url) {
            const response = await fetch(`${url}?${filterQuery()}`, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Dashboard request failed');
            return response.json();
        }

        async function loadOverview() {
            const data = await fetchJson(inventoryApi.overview);
            document.getElementById('overviewCards').innerHTML = data.cards.map(card => `
                <div class="metric-card tone-${card.tone}">
                    <div class="metric-label">${card.label}</div>
                    <div class="metric-value">${formatValue(card)}</div>
                    <div class="metric-meta">${card.meta || data.filter.label}</div>
                </div>
            `).join('');

            const labels = {
                pending_purchase_orders: 'Pending PO',
                received_purchase_orders: 'Received PO',
                pending_quotations: 'Pending Quotations',
                active_warehouses: 'Active Warehouses',
                active_suppliers: 'Active Suppliers',
            };

            document.getElementById('pipelineGrid').innerHTML = Object.entries(data.pipeline).map(([key, value]) => `
                <div class="pipeline-item">
                    <div class="metric-label">${labels[key] || key}</div>
                    <div class="metric-value">${Number(value || 0).toLocaleString()}</div>
                </div>
            `).join('');
        }

        async function loadRelationships() {
            const data = await fetchJson(inventoryApi.relationships);
            renderRelation('supplierRelation', data.supplier);
            renderRelation('customerRelation', data.customer);
        }

        function renderRelation(targetId, rows) {
            document.getElementById(targetId).innerHTML = rows.map(row => `
                <div class="relation-row">
                    <span>${row.label}</span>
                    <strong>${formatMoney(row.value)}</strong>
                </div>
            `).join('');
        }

        async function loadTrends() {
            const data = await fetchJson(inventoryApi.trends);
            const ctx = document.getElementById('purchaseTrendChart').getContext('2d');

            if (purchaseTrendChart) {
                purchaseTrendChart.destroy();
            }

            purchaseTrendChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Purchase Value',
                            data: data.values,
                            backgroundColor: 'rgba(37, 99, 235, 0.35)',
                            borderColor: '#2563eb',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        {
                            label: 'PO Count',
                            data: data.counts,
                            type: 'line',
                            borderColor: '#059669',
                            backgroundColor: '#059669',
                            yAxisID: 'y1',
                            tension: 0.35,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Value' } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Count' } },
                    }
                }
            });
        }

        async function loadRecent() {
            const data = await fetchJson(inventoryApi.recent);
            document.getElementById('recentPurchasesBody').innerHTML = data.purchases.length ? data.purchases.map(row => `
                <tr>
                    <td>${row.code || '-'}</td>
                    <td>${row.supplier_name || '-'}</td>
                    <td>${row.date || '-'}</td>
                    <td class="text-right">${formatMoney(row.subtotal)}</td>
                    <td class="text-right">${formatMoney(row.other_charge_amount)}</td>
                    <td class="text-right">${formatMoney(row.calculated_discount_amount)}</td>
                    <td class="text-right"><strong>${formatMoney(row.total)}</strong></td>
                </tr>
            `).join('') : '<tr><td colspan="7" class="text-muted">No received purchases found.</td></tr>';

            document.getElementById('recentReturns').innerHTML = data.returns.length ? data.returns.map(row => `
                <div class="relation-row">
                    <span>${row.code || '-'}<br><small class="text-muted">${row.supplier_name || '-'} · ${row.date || '-'}</small></span>
                    <strong>${formatMoney(row.total)}</strong>
                </div>
            `).join('') : '<div class="text-muted">No purchase returns found.</div>';
        }

        async function loadDashboard() {
            await Promise.all([
                loadOverview(),
                loadRelationships(),
                loadTrends(),
                loadRecent(),
            ]);
        }

        document.getElementById('inventoryPreset').addEventListener('change', function () {
            document.querySelectorAll('.custom-date').forEach(el => {
                el.classList.toggle('d-none', this.value !== 'custom');
            });
        });

        document.getElementById('inventoryApplyFilter').addEventListener('click', loadDashboard);
        loadDashboard();
    </script>
@endsection
