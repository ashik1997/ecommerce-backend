@extends('backend.master')

@section('header_css')
@endsection

@section('page_title')
    Dashboard
@endsection

@section('page_heading')
    Overview
@endsection

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- LAYOUT -->
    <div class="dashboard_content">
        <div class="layout">
    
            <!-- TOPBAR -->
            <header class="topbar">
                <span class="topbar-brand"><i class="fa-solid fa-store" style="font-size:16px;"></i> Analytics</span>
                <div class="topbar-divider"></div>
                <div class="store-badge"><span class="store-dot"></span> {{ auth()->user()->name }}</div>
                <div class="date-tabs">
                    <button class="date-tab" onclick="filterData('today')">Today</button>
                    <button class="date-tab" onclick="filterData('yesterday')">Yesterday</button>
                    <button class="date-tab" onclick="filterData('this_week')">This Week</button>
                    <button class="date-tab" onclick="filterData('last_30_days')">Last 30 Days</button>
                    <button class="date-tab active" onclick="filterData('this_month')">This Month</button>
                    <button class="date-tab" onclick="filterData('custom')">Custom ▾</button>
                    <form id="date-filter-form" style="display:none;">
                        <input type="date" class="form-control" name="from" id="date-from">
                        <input type="date" class="form-control" name="to" id="date-to">
                        <button type="button" class="btn btn-info" onclick="filterDataCustom()">Apply</button>
                    </form>
                    <script></script>
                </div>
            </header>
    
            <!-- MAIN -->
            <main class="main">
    
                <!-- PAGE HEADER -->
                <div class="page-header">
                    <div>
                        <div class="page-title"><i class="fa-solid fa-chart-pie"
                                style="color:var(--teal);font-size:18px;margin-right:8px;"></i>Business Overview</div>
                        <div class="page-sub"><i class="fa-regular fa-clock" style="margin-right:4px;"></i> <span
                                id="overview-date">This Month</span></div>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button class="btn-primary"><i class="fa-solid fa-plus"></i> New Order</button>
                        <button class="btn-primary"
                            style="background:linear-gradient(135deg,#334155,#1e293b);box-shadow:0 4px 14px rgba(0,0,0,.2);">
                            <i class="fa-solid fa-download"></i> Export
                        </button>
                    </div>
                </div>
    
                <!-- KPI CARDS -->
                <div class="kpi-row">
                    <div class="kpi-card g-teal">
                        <div class="kpi-icon"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                        <div class="kpi-label">Profit From Sale</div>
                        <div class="kpi-value" id="stat-total-sales">৳0</div>
                        <div class="kpi-change"><i class="fa-solid fa-arrow-trend-up"></i> +18.4%</div>
                        <div class="kpi-sub">vs last month · ৳8,47,320</div>
                    </div>
    
                    {{-- total profit --}}
                    <div class="kpi-card g-teal">
                        <div class="kpi-icon"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                        <div class="kpi-label">Total Income</div>
                        <div class="kpi-value" id="stat-total-profit">৳0</div>
                        <div class="kpi-change"><i class="fa-solid fa-arrow-trend-up"></i> +18.4%</div>
                        <div class="kpi-sub">vs last month · ৳8,47,320</div>
                    </div>
    
                    <div class="kpi-card g-emerald">
                        <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div class="kpi-label">Total Delivered</div>
                        <div class="kpi-value" id="stat-delivered-sales">৳0</div>
                        <div class="kpi-change"><i class="fa-solid fa-arrow-trend-up"></i> +12.7%</div>
                        <div class="kpi-sub">27.4% margin · ৳2,31,840</div>
                    </div>
    
                    <div class="kpi-card g-amber">
                        <div class="kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <div class="kpi-label">Total Due</div>
                        <div class="kpi-value" id="stat-total-due">৳0</div>
                        <div class="kpi-change"><i class="fa-solid fa-arrow-trend-down"></i> +8.2%</div>
                        <div class="kpi-sub">COD + Online pending</div>
                    </div>
    
                    <div class="kpi-card g-blue">
                        <div class="kpi-icon"><i class="fa-solid fa-box-open"></i></div>
                        <div class="kpi-label">Total Orders</div>
                        <div class="kpi-value" id="stat-total-orders">0</div>
                        <div class="kpi-change"><i class="fa-solid fa-arrow-trend-up"></i> +22.1%</div>
                        <div class="kpi-sub">vs last period</div>
                    </div>
    
                    <div class="kpi-card g-purple">
                        <div class="kpi-icon"><i class="fa-solid fa-rotate-left"></i></div>
                        <div class="kpi-label">Return Rate</div>
                        <div class="kpi-value" id="stat-return-rate">0%</div>
                        <div class="kpi-change"><i class="fa-solid fa-arrow-trend-up"></i> +2.1%</div>
                        <div class="kpi-sub">141 returns this month</div>
                    </div>
    
                    <div class="kpi-card g-slate">
                        <div class="kpi-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                        <div class="kpi-label">Avg Order Value</div>
                        <div class="kpi-value" id="stat-avg-order-value">৳0</div>
                        <div class="kpi-change"><i class="fa-solid fa-arrow-trend-up"></i> +5.8%</div>
                        <div class="kpi-sub">per order average</div>
                    </div>
                </div>
    
                <!-- ORDER STATUS -->
                <div class="sec-header">
                    <div class="sec-title"><i class="fa-solid fa-truck-fast"></i> Order & Courier Status</div>
                    <a class="sec-link" href="#">View all orders <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="status-row">
                    <div class="status-card" style="animation-delay:.05s">
                        <div class="status-top">
                            <div class="status-icon" style="background:#f1f5f9;color:#64748b;"><i
                                    class="fa-solid fa-clock"></i></div>
                            <span class="status-pct" style="background:#f1f5f9;color:#64748b;">14.8%</span>
                        </div>
                        <div class="status-count">184</div>
                        <div class="status-label">Pending</div>
                        <div class="status-bar">
                            <div class="status-bar-fill" style="width:14.8%;background:#94a3b8;"></div>
                        </div>
                    </div>
                    <div class="status-card" style="animation-delay:.10s">
                        <div class="status-top">
                            <div class="status-icon" style="background:#eff6ff;color:#3b82f6;"><i
                                    class="fa-solid fa-gears"></i></div>
                            <span class="status-pct" style="background:#eff6ff;color:#3b82f6;">25.0%</span>
                        </div>
                        <div class="status-count" style="color:#3b82f6;">312</div>
                        <div class="status-label">Processing</div>
                        <div class="status-bar">
                            <div class="status-bar-fill" style="width:25%;background:#3b82f6;"></div>
                        </div>
                    </div>
                    <div class="status-card" style="animation-delay:.15s">
                        <div class="status-top">
                            <div class="status-icon" style="background:#fffbeb;color:#f59e0b;"><i
                                    class="fa-solid fa-truck"></i></div>
                            <span class="status-pct" style="background:#fffbeb;color:#f59e0b;">34.2%</span>
                        </div>
                        <div class="status-count" style="color:#f59e0b;">426</div>
                        <div class="status-label">In Transit</div>
                        <div class="status-bar">
                            <div class="status-bar-fill" style="width:34.2%;background:#f59e0b;"></div>
                        </div>
                    </div>
                    <div class="status-card" style="animation-delay:.20s">
                        <div class="status-top">
                            <div class="status-icon" style="background:#ecfdf5;color:#10b981;"><i
                                    class="fa-solid fa-circle-check"></i></div>
                            <span class="status-pct" style="background:#ecfdf5;color:#10b981;">14.8%</span>
                        </div>
                        <div class="status-count" style="color:#10b981;">184</div>
                        <div class="status-label">Delivered</div>
                        <div class="status-bar">
                            <div class="status-bar-fill" style="width:14.8%;background:#10b981;"></div>
                        </div>
                    </div>
                    <div class="status-card" style="animation-delay:.25s;border:1px solid #fecaca;">
                        <div class="status-top">
                            <div class="status-icon" style="background:#fef2f2;color:#ef4444;"><i
                                    class="fa-solid fa-rotate-left"></i></div>
                            <span class="status-pct" style="background:#fef2f2;color:#ef4444;">⚠ 11.3%</span>
                        </div>
                        <div class="status-count" style="color:#ef4444;">141</div>
                        <div class="status-label">Returned/Cancelled</div>
                        <div class="status-bar">
                            <div class="status-bar-fill" style="width:11.3%;background:#ef4444;"></div>
                        </div>
                    </div>
                </div>
    
                <!-- COURIERS -->
                <div class="sec-header">
                    <div class="sec-title"><i class="fa-solid fa-motorcycle"></i> Courier Breakdown</div>
                </div>
                <div class="courier-row">
                    <div class="courier-card">
                        <div class="courier-ico" style="background:#eff6ff;color:#3b82f6;"><i
                                class="fa-solid fa-motorcycle"></i></div>
                        <div>
                            <div class="courier-name">Pathao</div>
                            <div class="courier-stat">621 delivered · 98 pending</div>
                        </div>
                        <div class="courier-cnt">621</div>
                    </div>
                    <div class="courier-card">
                        <div class="courier-ico" style="background:#fef2f2;color:#ef4444;"><i
                                class="fa-solid fa-truck-fast"></i></div>
                        <div>
                            <div class="courier-name">RedX</div>
                            <div class="courier-stat">389 delivered · 52 pending</div>
                        </div>
                        <div class="courier-cnt">389</div>
                    </div>
                    <div class="courier-card">
                        <div class="courier-ico" style="background:#ecfdf5;color:#10b981;"><i
                                class="fa-solid fa-van-shuttle"></i></div>
                        <div>
                            <div class="courier-name">SA Paribahan</div>
                            <div class="courier-stat">237 delivered · 34 pending</div>
                        </div>
                        <div class="courier-cnt">237</div>
                    </div>
                    <div class="courier-card">
                        <div class="courier-ico" style="background:#f5f3ff;color:#8b5cf6;"><i class="fa-solid fa-box"></i>
                        </div>
                        <div>
                            <div class="courier-name">Sundarban</div>
                            <div class="courier-stat">115 delivered · 16 pending</div>
                        </div>
                        <div class="courier-cnt">115</div>
                    </div>
                    <div class="courier-card">
                        <div class="courier-ico" style="background:#fffbeb;color:#f59e0b;"><i class="fa-solid fa-truck"></i>
                        </div>
                        <div>
                            <div class="courier-name">Steadfast</div>
                            <div class="courier-stat">88 delivered · 12 pending</div>
                        </div>
                        <div class="courier-cnt">88</div>
                    </div>
                </div>
    
                <!-- FUNNEL -->
                <div class="sec-header">
                    <div class="sec-title"><i class="fa-solid fa-filter"></i> Sales Funnel · Quotations → Conversions
                    </div>
                </div>
                <div class="funnel-row" style="margin-bottom:22px;">
                    <div class="funnel-card" style="background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;">
                        <div class="funnel-label"><i class="fa-solid fa-file-lines" style="margin-right:6px;"></i>Quotations
                            Created</div>
                        <div class="funnel-val">1,834</div>
                        <div class="funnel-sub">৳14,23,200 total value</div>
                        <div class="funnel-bar">
                            <div class="funnel-bar-fill" style="width:100%;"></div>
                        </div>
                    </div>
                    <div class="funnel-card" style="background:linear-gradient(135deg,#059669,#0d9488);color:#fff;">
                        <div class="funnel-label"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>Converted
                            to Orders</div>
                        <div class="funnel-val">1,247 <span style="font-size:16px;opacity:.8">· 67.9%</span></div>
                        <div class="funnel-sub">৳8,47,320 · 67.9% conversion rate</div>
                        <div class="funnel-bar">
                            <div class="funnel-bar-fill" style="width:67.9%;"></div>
                        </div>
                    </div>
                    <div class="funnel-card" style="background:linear-gradient(135deg,#dc2626,#9f1239);color:#fff;">
                        <div class="funnel-label"><i class="fa-solid fa-xmark" style="margin-right:6px;"></i>Lost
                            Quotations</div>
                        <div class="funnel-val">587</div>
                        <div class="funnel-sub">৳5,75,880 lost potential · 32.1%</div>
                        <div class="funnel-bar">
                            <div class="funnel-bar-fill" style="width:32.1%;"></div>
                        </div>
                    </div>
                </div>
    
                <!-- CHARTS ROW 1 -->
                <div class="sec-header">
                    <div class="sec-title"><i class="fa-solid fa-chart-area"></i> Revenue & Sales Analytics</div>
                </div>
                <div class="charts-row">
                    <div class="card">
                        <div class="sec-header" style="margin-bottom:12px;">
                            <div style="font-size:13px;font-weight:700;color:var(--text);">Revenue Trend <span
                                    style="font-size:11px;color:var(--muted);font-weight:400;">(Daily)</span></div>
                            <span
                                style="font-size:11px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:20px;padding:3px 9px;font-weight:700;"><i
                                    class="fa-solid fa-arrow-trend-up"></i> +18.4%</span>
                        </div>
                        <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
                    </div>
                    <div class="card">
                        <div class="sec-header" style="margin-bottom:12px;">
                            <div style="font-size:13px;font-weight:700;color:var(--text);">Revenue vs Expenses vs Profit
                            </div>
                        </div>
                        <div class="chart-wrap"><canvas id="revExpChart"></canvas></div>
                    </div>
                </div>
    
                <!-- CHARTS ROW 2 -->
                <div class="charts-row-3">
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Top 10 Products by Revenue</div>
                        <div class="chart-wrap"><canvas id="productChart"></canvas></div>
                    </div>
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Payment Methods</div>
                        <div class="chart-wrap"><canvas id="paymentChart"></canvas></div>
                    </div>
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Orders by Category</div>
                        <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
                    </div>
                </div>
    
                <!-- BOTTOM CHARTS -->
                <div class="charts-row">
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Orders Count & Avg Order Value
                        </div>
                        <div class="chart-wrap"><canvas id="ordersAvgChart"></canvas></div>
                    </div>
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Revenue Share by Category</div>
                        <div class="chart-wrap"><canvas id="catRevenueChart"></canvas></div>
                    </div>
                </div>
    
                <!-- TABLES -->
                <div class="sec-header">
                    <div class="sec-title"><i class="fa-solid fa-table-list"></i> Orders & Products</div>
                </div>
                <div class="table-grid">
                    <!-- Pending Orders -->
                    <div class="card" style="padding:0;">
                        <div
                            style="padding:16px 20px;border-bottom:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;">
                            <div style="font-size:13px;font-weight:700;">Pending & High-Value Orders</div>
                            <a class="sec-link" href="#" style="font-size:11px;">View all <i
                                    class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Days</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Trending Products -->
                    <div class="card" style="padding:0;">
                        <div
                            style="padding:16px 20px;border-bottom:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;">
                            <div style="font-size:13px;font-weight:700;">Trending Products</div>
                            <a class="sec-link" href="#" style="font-size:11px;">View all <i
                                    class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Sold</th>
                                        <th>Rate</th>
                                        <th>Revenue</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
    
                <!-- BOTTOM ROW: CATEGORY + CUSTOMERS + EXPENSES -->
                <div class="bottom-row">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <!-- Category Revenue -->
                        <div class="card">
                            <div style="font-size:13px;font-weight:700;margin-bottom:14px;"><i class="fa-solid fa-layer-group"
                                    style="color:var(--teal);margin-right:6px;"></i>Revenue by Category</div>
                            <div id="catRevenueList"></div>
                        </div>
                        <!-- Expenses -->
                        <div class="card">
                            <div style="font-size:13px;font-weight:700;margin-bottom:14px;"><i class="fa-solid fa-receipt"
                                    style="color:var(--teal);margin-right:6px;"></i>Expenses by Account</div>
                            <div id="expenseList"></div>
                        </div>
                    </div>
                    <!-- Right -->
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <!-- Customers -->
                        <div class="card">
                            <div style="font-size:13px;font-weight:700;margin-bottom:14px;"><i class="fa-solid fa-users"
                                    style="color:var(--teal);margin-right:6px;"></i>Customer Breakdown</div>
                            <div class="cust-ratio">
                                <div class="cust-item">
                                    <div class="cust-icon" style="background:#eff6ff;color:#3b82f6;"><i
                                            class="fa-solid fa-user-plus"></i></div>
                                    <div class="cust-info">
                                        <div class="cust-name">New Customers</div>
                                        <div class="cust-sub">54.8% · First-time buyers</div>
                                    </div>
                                    <div class="cust-num">684</div>
                                </div>
                                <div
                                    style="height:6px;background:#f0f2f7;border-radius:6px;overflow:hidden;margin:-6px 0 4px;">
                                    <div
                                        style="height:100%;width:54.8%;background:linear-gradient(90deg,#3b82f6,#6366f1);border-radius:6px;">
                                    </div>
                                </div>
                                <div class="cust-item">
                                    <div class="cust-icon" style="background:#ecfdf5;color:#10b981;"><i
                                            class="fa-solid fa-rotate"></i></div>
                                    <div class="cust-info">
                                        <div class="cust-name">Returning</div>
                                        <div class="cust-sub">45.2% · Repeat buyers</div>
                                    </div>
                                    <div class="cust-num" style="color:#10b981;">563</div>
                                </div>
                                <div
                                    style="height:6px;background:#f0f2f7;border-radius:6px;overflow:hidden;margin:-6px 0 4px;">
                                    <div
                                        style="height:100%;width:45.2%;background:linear-gradient(90deg,#10b981,#0d9488);border-radius:6px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Insight -->
                        <div class="insight-card">
                            <div class="insight-icon">🎯</div>
                            <div class="insight-title">Return Rate Alert</div>
                            <div class="insight-body">Return rate at 11.3% — above 8% target. Saree & Ethnic category is
                                the top offender. Review sizing charts & product photos.</div>
                            <a class="insight-btn" href="#"><i class="fa-solid fa-arrow-right"></i> Investigate
                                Now</a>
                        </div>
                    </div>
                </div>
    
                <!-- MONTHLY SUMMARY TABLE -->
                <div class="sec-header">
                    <div class="sec-title"><i class="fa-solid fa-table"></i> Monthly P&L Summary</div>
                </div>
                <div class="card monthly-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="col-head">Gross Sales</th>
                                <th class="col-head">Discounts</th>
                                <th class="col-head">Returns</th>
                                <th class="col-head">Net Sales</th>
                                <th class="col-head">Total Sales</th>
                                <th class="col-head2">Revenue</th>
                                <th class="col-head2">Direct Costs</th>
                                <th class="col-head2">Gross Profit</th>
                                <th class="col-head2">Gross Margin</th>
                                <th class="col-head2">Op. Expenses</th>
                                <th class="col-head2">Net Profit</th>
                                <th class="col-head2">Net Margin</th>
                            </tr>
                        </thead>
                        <tbody id="monthlyTableBody"></tbody>
                    </table>
                </div>
    
                <div style="padding: 24px 0 8px; text-align:center; font-size:11px; color:var(--muted);">
                    ShopFlow Admin v2.4 &nbsp;·&nbsp; Dhaka Fashion Store &nbsp;·&nbsp; Data refreshes every 5 minutes
                    &nbsp;·&nbsp;
                    <a href="#" style="color:var(--teal);text-decoration:none;font-weight:600;">Refresh now <i
                            class="fa-solid fa-arrows-rotate"></i></a>
                </div>
    
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // currency convert 
            function formatCurrency(amount) {
                return '৳' + parseFloat(amount)
                    .toFixed(2)
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            // ── TABLES ────────────────────────────────────────────────────
            function loadPendingHighValueOrders(start_date = '', end_date = '') {
                axios.get('/api/pending-high-value-orders', {
                        params: {
                            start_date: start_date,
                            end_date: end_date
                        }
                    })
                    .then(response => {
                        const orders = response.data; // use API data
                        const tbody = document.getElementById('ordersTableBody');

                        tbody.innerHTML = ''; // clear before append

                        orders.forEach(o => {
                            const badgeColors = {
                                pending: 'info',
                                cancelled: 'danger',
                                invoiced: 'success',
                                delivered: 'primary'
                            };

                            const icon =
                                o.order_status === 'cancelled' ? 'times' :
                                o.order_status === 'invoiced' ? 'file-invoice' :
                                o.order_status === 'delivered' ? 'truck' : 'clock';

                            const statusBadge = `
                            <span class="badge badge-${badgeColors[o.order_status] ?? 'secondary'}">
                                <i class="fa-solid fa-${icon}"></i>
                                ${o.order_status.charAt(0).toUpperCase() + o.order_status.slice(1)}
                            </span>
                        `;

                            const days = Math.floor(
                                (new Date() - new Date(o.created_at)) / (1000 * 60 * 60 * 24)
                            );

                            const daysColor =
                                days >= 4 ? 'color:#ef4444;font-weight:700;' :
                                days >= 2 ? 'color:#f59e0b;font-weight:700;' :
                                'color:#6b7a99;';

                            const bg = o.order_note ? 'background:#fffdf0;' : '';

                            tbody.innerHTML += `
                            <tr style="${bg}">
                                <td class="td-id">${o.order_code}</td>
                                <td><div class="td-name">${o.customer_name}</div></td>
                                <td class="td-amount">${formatCurrency(o.total)}</td>
                                <td>${statusBadge}</td>
                                <td><span style="${daysColor}">${days}d</span></td>
                                <td>
                                    <div style="display:flex;gap:5px;">
                                        <button class="action-btn action-btn-ghost">View</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching orders:', error);
                    });
            }

            async function loadTrendingProducts(start_date = '', end_date = '') {
                try {
                    const response = await axios.get('/api/trending-products', {
                        params: {
                            start_date: start_date,
                            end_date: end_date
                        }
                    }); // 👈 your API

                    const trendingProducts = response.data;

                    const ptbody = document.getElementById('productsTableBody');

                    let rows = '';

                    trendingProducts.forEach(p => {

                        const rate = p.price;

                        const rateColor = rate >= 900 ? '#10b981' :
                            rate >= 800 ? '#f59e0b' :
                            '#ef4444';

                        const stockEl = p.total_sold > 10 ?
                            '<span class="stock-ok"><i class="fa-solid fa-circle-check"></i> OK</span>' :
                            p.total_sold === 1 ?
                            '<span class="stock-low"><i class="fa-solid fa-triangle-exclamation"></i> Low</span>' :
                            '<span class="stock-critical"><i class="fa-solid fa-circle-exclamation"></i> Critical</span>';

                        rows += `
                    <tr>
                        <td>
                            <div class="td-name">${p.name}</div>
                            <div class="td-sub">${p.created_at ?? ''}</div>
                        </td>
                        <td style="font-weight:700;">${p.total_sold}</td>
                        <td><span style="font-weight:800;color:${rateColor};">${formatCurrency(p.price)}</span></td>
                        <td style="font-weight:700;font-size:12px;">${formatCurrency(p.total_revenue)}</td>
                        <td>${stockEl}</td>
                    </tr>`;
                    });

                    ptbody.innerHTML = rows;

                } catch (error) {
                    console.error('Error loading trending products:', error);
                }
            }

            function formatCurrency(amount) {
                return '৳' + parseFloat(amount).toLocaleString('en-BD', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function loadOverview(start_date = '', end_date = '') {
                axios.get('/api/dashboard/overview', {
                        params: {
                            start_date: start_date,
                            end_date: end_date
                        }
                    })
                    .then(response => {

                        if (!response.data.success) return;

                        const data = response.data.data;

                        // ======================
                        // VALUE UPDATE ONLY
                        // ======================
                        document.getElementById('stat-total-sales').textContent =
                            formatCurrency(data.sales.total_sales);

                        document.getElementById('stat-delivered-sales').textContent =
                            formatCurrency(data.sales.delivered_sales);

                        document.getElementById('stat-total-due').textContent =
                            formatCurrency(data.due.total_due);

                        document.getElementById('stat-total-orders').textContent =
                            data.orders.total_orders;

                        // Avg Order Value
                        let avg = data.orders.total_orders > 0 ?
                            data.sales.total_sales / data.orders.total_orders :
                            0;

                        document.getElementById('stat-avg-order-value').textContent =
                            formatCurrency(avg);

                        // Return Rate (example calculation)
                        let returnRate = data.orders.total_orders > 0 ?
                            (data.orders.canceled_orders / data.orders.total_orders) * 100 :
                            0;

                        document.getElementById('stat-return-rate').textContent =
                            returnRate.toFixed(1) + '%';

                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function loadAllfunctions(start_date, end_date) {
                // initial call
                console.log("Loading data:", start_date, end_date);

                loadPendingHighValueOrders(start_date, end_date);
                loadTrendingProducts(start_date, end_date);
                loadOverview(start_date, end_date);
            }

            function filterDataCustom() {
                let from_date = document.getElementById('date-from').value;
                let to_date = document.getElementById('date-to').value;
                if (from_date && to_date) {
                    loadAllfunctions(new Date(from_date), new Date(to_date));
                    document.getElementById('overview-date').textContent = `${from_date} to ${to_date}`;
                }
            }

            function filterData(period) {
                document.querySelectorAll('.date-tab').forEach(tab => tab.classList.remove('active'));
                event.target.classList.add('active');
                document.getElementById('overview-date').textContent = event.target.textContent;
                if (period === 'custom') {
                    document.getElementById('date-filter-form').style.display = 'flex';
                } else {
                    document.getElementById('date-filter-form').style.display = 'none';
                }
                if (period == 'today') {
                    // loadAllfunctions to fetch and update stats for today
                    let start_date = new Date();
                    let end_date = new Date();
                    loadAllfunctions(start_date, end_date);
                } else if (period == 'yesterday') {
                    // loadAllfunctions to fetch and update stats for yesterday
                    let start_date = new Date();
                    start_date.setDate(start_date.getDate() - 1);
                    let end_date = new Date();
                    end_date.setDate(end_date.getDate() - 1);
                    loadAllfunctions(start_date, end_date);
                } else if (period == 'this_week') {
                    // loadAllfunctions to fetch and update stats for this week
                    let start_date = new Date();
                    start_date.setDate(start_date.getDate() - start_date.getDay());
                    let end_date = new Date();
                    end_date.setDate(end_date.getDate() + (6 - end_date.getDay()));
                    loadAllfunctions(start_date, end_date);
                } else if (period == 'last_30_days') {
                    // loadAllfunctions to fetch and update stats for last 30 days
                    let start_date = new Date();
                    start_date.setDate(start_date.getDate() - 29);
                    let end_date = new Date();
                    loadAllfunctions(start_date, end_date);
                } else if (period == 'this_month') {
                    // loadAllfunctions to fetch and update stats for this month
                    let start_date = new Date();
                    start_date.setDate(1);
                    let end_date = new Date();
                    end_date.setMonth(end_date.getMonth() + 1);
                    end_date.setDate(0);
                    loadAllfunctions(start_date, end_date);
                } else {
                    // default to this month
                    let start_date = new Date();
                    start_date.setDate(1);
                    let end_date = new Date();
                    end_date.setMonth(end_date.getMonth() + 1);
                    end_date.setDate(0);
                    loadAllfunctions(start_date, end_date);
                }

            }
            // default load this month data
            loadAllfunctions();

            Object.assign(window, {
                filterData,
                filterDataCustom
            });
        });



        // ── DATA ──────────────────────────────────────────────────────

        function formatCurrency(amount) {
            return '৳' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        const revenueData = {
            labels: ['Feb 1', 'Feb 5', 'Feb 10', 'Feb 15', 'Feb 20', 'Feb 25', 'Mar 1', 'Mar 3'],
            values: [42000, 58000, 49000, 73000, 91000, 68000, 105000, 88000]
        };

        const revExpData = {
            labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
            revenue: [276100, 305200, 294000, 319900, 316100, 66500],
            expenses: [202400, 218300, 213800, 208400, 205600, 60800],
            profit: [43800, 68800, 80200, 111600, 115000, 5700]
        };

        const products = ['Organza Saree', 'Cotton Panjabi', 'Sneakers Pro', 'Kantha Kurti', 'Leather Wallet',
            'Jamdani Saree', 'Kids Frock', 'Men\'s Chino', 'Silk Dupatta', 'Tote Bag'
        ];
        const productRevs = [182000, 147000, 134000, 119000, 98000, 87000, 76000, 65000, 54000, 43000];

        const paymentLabels = ['COD', 'bKash', 'Nagad', 'Card', 'Bank'];
        const paymentVals = [52, 28, 11, 6, 3];
        const paymentColors = ['#0d9488', '#e91e8c', '#f59e0b', '#10b981', '#8b5cf6'];

        const catLabels = ['Saree & Ethnic', 'Men\'s Wear', 'Kids', 'Footwear', 'Bags', 'Accessories'];
        const catOrders = [312, 241, 189, 156, 134, 98];

        const ordersAvgData = {
            labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
            orders: [75, 89, 89, 113, 137, 31],
            avg: [3000, 3000, 2000, 3000, 3000, 2000]
        };

        const catRevLabels = ['Saree & Ethnic', 'Men\'s Wear', 'Footwear', 'Kids', 'Other'];
        const catRevVals = [38, 24, 16, 12, 10];
        const catRevColors = ['#0d9488', '#10b981', '#f59e0b', '#8b5cf6', '#6b7280'];

        // ── CHARTS ────────────────────────────────────────────────────
        const font = {
            family: "'DM Sans', sans-serif"
        };
        Chart.defaults.font.family = font.family;
        Chart.defaults.color = '#9ca3af';

        // Revenue Trend
        new Chart('revenueChart', {
            type: 'line',
            data: {
                labels: revenueData.labels,
                datasets: [{
                    label: 'Revenue (৳)',
                    data: revenueData.values,
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13,148,136,.1)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: .45,
                    pointBackgroundColor: '#0d9488',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: c => '৳' + c.raw.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            callback: v => '৳' + (v / 1000).toFixed(0) + 'k'
                        }
                    }
                }
            }
        });

        // Rev/Exp/Profit
        new Chart('revExpChart', {
            type: 'bar',
            data: {
                labels: revExpData.labels,
                datasets: [{
                        label: 'Revenue',
                        data: revExpData.revenue,
                        backgroundColor: 'rgba(59,130,246,.7)',
                        borderRadius: 5
                    },
                    {
                        label: 'Expenses',
                        data: revExpData.expenses,
                        backgroundColor: 'rgba(156,163,175,.5)',
                        borderRadius: 5
                    },
                    {
                        label: 'Net Profit',
                        data: revExpData.profit,
                        backgroundColor: 'rgba(16,185,129,.8)',
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            callback: v => '৳' + (v / 1000).toFixed(0) + 'k'
                        }
                    }
                }
            }
        });

        // Top Products
        new Chart('productChart', {
            type: 'bar',
            data: {
                labels: products,
                datasets: [{
                    label: 'Revenue',
                    data: productRevs,
                    backgroundColor: 'rgba(13,148,136,.75)',
                    borderRadius: [0, 4, 4, 0],
                    hoverBackgroundColor: '#0d9488'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: c => '৳' + c.raw.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 9
                            },
                            callback: v => '৳' + (v / 1000).toFixed(0) + 'k'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            }
        });

        // Payment Pie
        new Chart('paymentChart', {
            type: 'doughnut',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentVals,
                    backgroundColor: paymentColors,
                    hoverOffset: 8,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: c => c.label + ': ' + c.raw + '%'
                        }
                    }
                }
            }
        });

        // Category Bar
        new Chart('categoryChart', {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Orders',
                    data: catOrders,
                    backgroundColor: 'rgba(139,92,246,.75)',
                    borderRadius: 5,
                    hoverBackgroundColor: '#8b5cf6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 8
                            },
                            maxRotation: 30
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Orders + Avg (dual axis)
        new Chart('ordersAvgChart', {
            type: 'bar',
            data: {
                labels: ordersAvgData.labels,
                datasets: [{
                        type: 'bar',
                        label: 'Orders Count',
                        data: ordersAvgData.orders,
                        backgroundColor: 'rgba(16,185,129,.7)',
                        borderRadius: 5,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Avg Order Value (৳)',
                        data: ordersAvgData.avg,
                        borderColor: '#e91e8c',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        tension: .4,
                        pointRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        },
                        position: 'left'
                    },
                    y1: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            callback: v => '৳' + (v / 1000).toFixed(0) + 'k'
                        },
                        position: 'right'
                    }
                }
            }
        });

        // Category Revenue Donut
        new Chart('catRevenueChart', {
            type: 'doughnut',
            data: {
                labels: catRevLabels,
                datasets: [{
                    data: catRevVals,
                    backgroundColor: catRevColors,
                    hoverOffset: 8,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: c => c.label + ': ' + c.raw + '%'
                        }
                    }
                }
            }
        });

        // ── TABLES ────────────────────────────────────────────────────


        // ── CATEGORY REVENUE LIST ──────────────────────────────────────
        const catData = [{
                name: 'Saree & Ethnic',
                pct: 38,
                color: '#0d9488'
            },
            {
                name: 'Men\'s Wear',
                pct: 24,
                color: '#10b981'
            },
            {
                name: 'Footwear',
                pct: 16,
                color: '#f59e0b'
            },
            {
                name: 'Kids',
                pct: 12,
                color: '#8b5cf6'
            },
            {
                name: 'Other',
                pct: 10,
                color: '#6b7280'
            },
        ];
        const catList = document.getElementById('catRevenueList');
        catData.forEach(c => {
            catList.innerHTML += `<div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:12px;font-weight:600;color:#1a2332;display:flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:${c.color};display:inline-block;"></span>${c.name}
                </span>
                <span style="font-size:12px;font-weight:800;color:#1a2332;">${c.pct}%</span>
                </div>
                <div style="height:6px;background:#f0f2f7;border-radius:6px;overflow:hidden;">
                <div style="height:100%;width:${c.pct}%;background:${c.color};border-radius:6px;transition:width .8s;"></div>
                </div>
            </div>`;
        });

        // ── EXPENSES LIST ──────────────────────────────────────────────
        const expenses = [{
                name: 'Wages & Salaries',
                amt: '৳4,58,000',
                pct: 41.29
            },
            {
                name: 'Subcontractors',
                amt: '৳3,68,000',
                pct: 33.18
            },
            {
                name: 'Rent (Office)',
                amt: '৳2,10,000',
                pct: 18.93
            },
            {
                name: 'Payroll Tax',
                amt: '৳59,900',
                pct: 5.4
            },
            {
                name: 'Payment Fees',
                amt: '৳15,300',
                pct: 1.38
            },
        ];
        const expList = document.getElementById('expenseList');
        expenses.forEach(e => {
            expList.innerHTML += `<div class="exp-row">
    <div class="exp-name">${e.name}</div>
    <div class="exp-amt">${e.amt}</div>
    <div class="exp-bar-wrap"><div class="exp-bar-fill" style="width:${e.pct}%;"></div></div>
    <div class="exp-pct">${e.pct}%</div>
  </div>`;
        });

        // ── MONTHLY TABLE ──────────────────────────────────────────────
        const monthly = [{
                m: 'Mar 2026',
                gs: 34497,
                disc: -5807,
                ret: -7217,
                ns: 40464,
                sh: 387,
                tax: 524,
                ts: 41375,
                rev: 66464,
                dc: 0,
                gp: 66464,
                gm: 100,
                oe: -60755,
                np: 5709,
                nm: 8.59
            },
            {
                m: 'Feb 2026',
                gs: 190770,
                disc: -30078,
                ret: -28255,
                ns: 222560,
                sh: 1730,
                tax: 4099,
                ts: 228389,
                rev: 316110,
                dc: -80500,
                gp: 235610,
                gm: 74.53,
                oe: -125105,
                np: 115041,
                nm: 36.39
            },
            {
                m: 'Jan 2026',
                gs: 182818,
                disc: -23729,
                ret: -37781,
                ns: 203417,
                sh: 1478,
                tax: 2806,
                ts: 207701,
                rev: 319946,
                dc: -80750,
                gp: 239196,
                gm: 74.76,
                oe: -127631,
                np: 111565,
                nm: 34.87
            },
            {
                m: 'Dec 2025',
                gs: 121700,
                disc: -20647,
                ret: -24025,
                ns: 143014,
                sh: 1045,
                tax: 2004,
                ts: 146063,
                rev: 294036,
                dc: -79580,
                gp: 214456,
                gm: 72.94,
                oe: -134238,
                np: 80218,
                nm: 27.28
            },
            {
                m: 'Nov 2025',
                gs: 118015,
                disc: -13990,
                ret: -37443,
                ns: 132455,
                sh: 1059,
                tax: 1211,
                ts: 134725,
                rev: 305182,
                dc: -79800,
                gp: 225382,
                gm: 73.85,
                oe: -138489,
                np: 68844,
                nm: 22.56
            },
            {
                m: 'Oct 2025',
                gs: 105744,
                disc: -17289,
                ret: -15863,
                ns: 122845,
                sh: 949,
                tax: 1201,
                ts: 124995,
                rev: 276103,
                dc: -62700,
                gp: 213403,
                gm: 77.29,
                oe: -139652,
                np: 43751,
                nm: 15.85
            },
        ];

        const mt = document.getElementById('monthlyTableBody');
        const fmt = n => n === 0 ? '৳0' : (n < 0 ? '-৳' : '৳') + Math.abs(n).toLocaleString();
        monthly.forEach((r, i) => {
            const isTotal = i === monthly.length;
            mt.innerHTML += `<tr ${i===0?'style="background:#f0faf9;font-weight:700;"':''}>
                <td style="font-weight:700;">${r.m}</td>
                <td class="col-pos">${fmt(r.gs)}</td>
                <td class="col-neg">${fmt(r.disc)}</td>
                <td class="col-neg">${fmt(r.ret)}</td>
                <td>${fmt(r.ns)}</td>
                <td>${fmt(r.ts)}</td>
                <td style="font-weight:700;">${fmt(r.rev)}</td>
                <td class="col-neg">${fmt(r.dc)}</td>
                <td class="col-pos">${fmt(r.gp)}</td>
                <td>${r.gm}%</td>
                <td class="col-neg">${fmt(r.oe)}</td>
                <td class="${r.np>0?'col-pos':'col-neg'}">${fmt(r.np)}</td>
                <td style="font-weight:700;">${r.nm}%</td>
            </tr>`;
        });

        // Date tabs
        document.querySelectorAll('.date-tab').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.date-tab').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
@endsection

@section('footer_js')
@endsection
