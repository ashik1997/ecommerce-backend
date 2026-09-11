@extends('backend.master')

@section('header_css')
    <style>
        .quotation_wrapper {
            --qm-brand: #6366f1;
            --qm-brand-light: #eef2ff;
        }
        .qm-stat-card {
            cursor: pointer;
            transition: box-shadow .15s, border-color .15s;
            border: 2px solid transparent;
        }
        .qm-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08) !important; }
        .qm-stat-active { border-color: var(--qm-brand) !important; background: var(--qm-brand-light) !important; }
        .stat-label { font-size: 11px; }
        .stat-total { font-size: 13px; font-weight: 700; }
        .sales_analytics_icon { opacity: .75; }
        .table tbody tr:hover { background: #fafbff; }
        /* .btn-action { display:inline-flex; align-items:center; justify-content:center;
                      width:28px; height:28px; border-radius:6px; font-size:14px;
                      text-decoration:none; cursor:pointer; border:none; background:transparent; }
        .btn-action--edit   { background:#e0f2fe; }
        .btn-action--del    { background:#fee2e2; }
        .btn-action--convert { background:#d1fae5; } */
        .tbl-footer { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-top:1px solid #e5e7eb; }
        .page-btn { padding:4px 10px; border:1px solid #d1d5db; border-radius:4px; background:#fff; cursor:pointer; margin:0 2px; font-size:13px; }
        .page-btn.active { background:var(--qm-brand); color:#fff; border-color:var(--qm-brand); }
        .page-btn:disabled { opacity:.45; cursor:not-allowed; }
        .badge-status { padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; }
        .qm-actions { display:flex; gap:4px; }
        .qm-actions {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #fff;
        }

        /* Different button colors */
        .btn-action--edit {
            background-color: #3498db;
        }

        .btn-action--convert {
            background-color: #27ae60;
        }

        .btn-action--del {
            background-color: #e74c3c;
        }

        /* Hover effect */
        .btn-action:hover {
            opacity: 0.85;
        }
    </style>
@endsection

@section('page_title') All Quotations @endsection
@section('page_heading') Quotations @endsection

@section('content')
<div id="quotation_wrapper" class="quotation_wrapper">

    {{-- Loader --}}
    <div v-show="fullScreenLoading" style="display:flex;" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background:rgba(0,0,0,.5);z-index:9999;">
        <div class="text-center text-white">
            <div class="spinner-border mb-2" style="width:3rem;height:3rem;" role="status"></div>
            <div>@{{ loadingText }}</div>
        </div>
    </div>

    {{-- Analytics cards --}}
    <div class="row g-2 mb-3" v-if="analytics">
        <div class="col-6 col-md-3 col-lg-2" v-for="(stat, key) in analytics" :key="key">
            <div class="card border-0 shadow-sm qm-stat-card" :class="{ 'qm-stat-active': filters.order_status === key || (key === 'all' && !filters.order_status) }"
                @click="setFilter(key === 'all' ? '' : key)">
                <div class="card-body p-3 d-flex align-items-center" style="gap:10px;">
                    <i :class="statIcon(key)" class="fa-lg sales_analytics_icon"></i>
                    <div>
                        <div class="stat-label text-muted text-capitalize">@{{ key }} (@{{ stat.count }})</div>
                        <div class="stat-total text-success">৳@{{ formatNumber(stat.total) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-2 justify-content-between flex-wrap">
                <div class="col-12 col-md-5 col-lg-4">
                    <input type="text" class="form-control form-control-sm"
                        placeholder="Search code, customer, phone…"
                        v-model="filters.search" @input="debouncedFetch">
                </div>
                <div class="col-auto d-flex gap-2">
                    <select class="form-control form-control-sm" v-model="filters.order_status" @change="fetchData(1)" style="width:140px;">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_review">In Review</option>
                        <option value="invoiced">Invoiced</option>
                        <option value="canceled">Canceled</option>
                    </select>
                    <select class="form-control form-control-sm" v-model="filters.warehouse_id" @change="fetchData(1)" style="width:160px;">
                        <option value="">All warehouses</option>
                        @foreach ($productWarehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->title }}</option>
                        @endforeach
                    </select>
                    <input type="date" class="form-control form-control-sm" v-model="filters.date_from" @change="fetchData(1)" style="width:140px;">
                    <input type="date" class="form-control form-control-sm" v-model="filters.date_to" @change="fetchData(1)" style="width:140px;">
                </div>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <span class="small text-muted">@{{ selectedIds.length }} selected</span>
        <select class="form-control form-control-sm" style="width:160px;" v-model="quickStatus" @change="bulkChangeStatus">
            <option value="">Change status…</option>
            <option value="pending">Pending</option>
            <option value="in_review">In Review</option>
            <option value="invoiced">Invoiced</option>
            <option value="canceled">Canceled</option>
        </select>
        <a href="{{ route('CreateProductOrderQuotation') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> New Quotation
        </a>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll">
                            </th>
                            <th>Quotation</th>
                            <th>Customer</th>
                            <th>Warehouse</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin"></i></td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td colspan="7" class="text-center py-4 text-muted">No quotations found.</td>
                        </tr>
                        <tr v-else v-for="row in rows" :key="row.id">
                            <td><input type="checkbox" :value="row.id" v-model="selectedIds"></td>
                            <td>
                                <div class="fw-semibold">@{{ row.order_code }}</div>
                                <div class="small text-muted">📅 @{{ row.sale_date }}</div>
                                <div class="small text-muted" v-if="row.due_date">🚚 @{{ row.due_date }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">@{{ row.customer_name }}</div>
                                <div class="small text-muted">@{{ row.customer_phone }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">@{{ row.warehouse_name }}</span></td>
                            <td>
                                <div class="small">Grand: <strong>৳@{{ formatNumber(row.total) }}</strong></div>
                                <div class="small text-success">Paid: ৳@{{ formatNumber(row.paid_amount) }}</div>
                                <div class="small text-danger" v-if="row.due_amount > 0">Due: ৳@{{ formatNumber(row.due_amount) }}</div>
                            </td>
                            <td>
                                <span class="badge-status" :class="statusClass(row.order_status)">@{{ row.order_status }}</span>
                            </td>
                            <td>
                                <div class="qm-actions">
                                    <a :href="editUrl(row.slug)" class="btn-action btn-action--edit" title="Edit">✏️ Edit</a>
                                    <button type="button" class="btn-action btn-action--convert" title="Convert to Order" @click="convertToOrder(row)">↗ Convert to Order</button>
                                    <button type="button" class="btn-action btn-action--del" title="Delete" @click="confirmDelete(row)">✕ Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="tbl-footer" v-if="pageInfo && pageInfo.total > 0">
                <span class="small text-muted">
                    Showing <strong>@{{ pageInfo.from }}–@{{ pageInfo.to }}</strong> of <strong>@{{ pageInfo.total }}</strong>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm" style="width:auto;" v-model="perPage" @change="fetchData(1)">
                        <option :value="10">10</option>
                        <option :value="20">20</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <div>
                        <button v-for="link in pageInfo.links" :key="link.label"
                            class="page-btn" :class="{ active: link.active }"
                            :disabled="!link.url"
                            @click="link.url && goToPage(link.url)"
                            v-html="link.label">
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer_js')
    <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var listUrl     = @json(route('QuotationListJson'));
        var editBase    = @json(url('/quotation/edit'));
        var deleteBase  = @json(url('/quotation/delete'));
        var statusUrl   = @json(route('QuotationQuickChangeStatus'));
        var convertBase = @json(url('/quotation/convert'));

        new Vue({
            el: '#quotation_wrapper',
            data: {
                rows: [],
                analytics: null,
                pageInfo: null,
                loading: false,
                fullScreenLoading: false,
                loadingText: 'Loading…',
                filters: { search: '', order_status: '', warehouse_id: '', date_from: '', date_to: '' },
                perPage: 20,
                selectedIds: [],
                selectAll: false,
                quickStatus: '',
                debounceTimer: null,
            },
            mounted: function () { this.fetchData(1); },
            methods: {
                fetchData: function (page) {
                    var vm = this;
                    vm.loading = true;
                    var p = { page: page || 1, per_page: vm.perPage };
                    Object.keys(vm.filters).forEach(function (k) { if (vm.filters[k]) p[k] = vm.filters[k]; });
                    var qs = new URLSearchParams(p).toString();
                    fetch(listUrl + '?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            vm.rows      = res.data.data || [];
                            vm.pageInfo  = res.data || null;
                            vm.analytics = res.analytics || null;
                            vm.loading   = false;
                        })
                        .catch(function () { vm.loading = false; });
                },
                debouncedFetch: function () {
                    var vm = this;
                    clearTimeout(vm.debounceTimer);
                    vm.debounceTimer = setTimeout(function () { vm.fetchData(1); }, 300);
                },
                goToPage: function (url) {
                    var page = new URL(url).searchParams.get('page');
                    if (page) this.fetchData(page);
                },
                setFilter: function (status) {
                    this.filters.order_status = status;
                    this.fetchData(1);
                },
                toggleSelectAll: function () {
                    this.selectedIds = this.selectAll ? this.rows.map(function (r) { return r.id; }) : [];
                },
                formatNumber: function (n) {
                    return Number(n || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                statusClass: function (s) {
                    return { pending: 'bg-warning text-dark', in_review: 'bg-info text-white', invoiced: 'bg-success text-white', canceled: 'bg-secondary text-white' }[s] || 'bg-secondary text-white';
                },
                statIcon: function (k) {
                    return { all: 'fas fa-file-text text-primary', pending: 'fas fa-clock text-warning', in_review: 'fas fa-search text-info', invoiced: 'fas fa-check-circle text-success', canceled: 'fas fa-times-circle text-secondary' }[k] || 'fas fa-circle';
                },
                editUrl: function (slug) { return editBase + '/' + slug; },
                bulkChangeStatus: function () {
                    var vm = this;
                    if (!vm.quickStatus) return;
                    if (!vm.selectedIds.length) { toastr.warning('Select at least one quotation.'); vm.quickStatus = ''; return; }
                    var status = vm.quickStatus;
                    Swal.fire({ title: 'Change status?', text: 'Set to "' + status + '" for ' + vm.selectedIds.length + ' quotation(s)?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes' })
                        .then(function (r) {
                            if (!r.isConfirmed) { vm.quickStatus = ''; return; }
                            vm.fullScreenLoading = true;
                            var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            fetch(statusUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ ids: vm.selectedIds, status: status }) })
                                .then(function (r) { return r.json(); })
                                .then(function (res) { vm.fullScreenLoading = false; vm.quickStatus = ''; res.success ? (toastr.success(res.message), vm.fetchData(1)) : toastr.error(res.message); })
                                .catch(function () { vm.fullScreenLoading = false; toastr.error('Network error.'); });
                        });
                },
                convertToOrder: function (row) {
                    Swal.fire({
                        title: 'Convert to Order?',
                        html: 'Open <strong>' + row.order_code + '</strong> in POS and pre-fill the order form.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Open POS',
                        cancelButtonText: 'Cancel',
                    }).then(function (r) {
                        if (!r.isConfirmed) return;
                        window.location.href = '/pos/desktop?quotation_id=' + row.id;
                    });
                },
                confirmDelete: function (row) {
                    var vm = this;
                    Swal.fire({ title: 'Delete quotation?', text: 'This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete' })
                        .then(function (r) {
                            if (!r.isConfirmed) return;
                            vm.fullScreenLoading = true;
                            vm.loadingText = 'Deleting…';
                            fetch(deleteBase + '/' + row.slug, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                                .then(function (r) { return r.json(); })
                                .then(function (res) { vm.fullScreenLoading = false; res.success ? (toastr.success(res.message), vm.fetchData(1)) : toastr.error(res.message); })
                                .catch(function () { vm.fullScreenLoading = false; toastr.error('Network error.'); });
                        });
                },
            }
        });
    });
    </script>
@endsection
