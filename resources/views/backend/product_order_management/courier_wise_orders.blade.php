@extends('backend.master')

@section('page_title')
    {{  request()->route('courier')?? '-' }} Courier Orders
@endsection

@section('page_heading')
    {{  request()->route('courier')?? '-' }} Courier Orders
@endsection

@section('content')
    @php
        $isDeliveryContext = request()->routeIs('delivery-management.*');
        $courierName = request()->route('courier') ?? 'Pathao';
        $courierOrdersRoute = $isDeliveryContext
            ? route('delivery-management.courier-wise-orders', ['courier' => $courierName])
            : route('courier-wise-orders', ['courier' => $courierName]);
    @endphp
    @if ($isDeliveryContext)
        @include('backend.delivery_management.partials.nav')
    @endif
    <div id="pathao_courier_status_app" class="card">

        {{-- ── Header ── --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">
                <i class="fas fa-truck me-2"></i> {{  request()->route('courier')?? '-' }} Track Orders
            </h5>
            <a href="{{ route('ViewAllProductOrder') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to orders
            </a>
        </div>

        {{-- ── Body ── --}}
        <div class="card-body">

            {{-- Filter bar --}}
            <div class="pt-filter-bar">
                <span class="pt-filter-label">Filter by status:</span>

                <button type="button" class="btn btn-sm"
                    :class="!courierStatusFilter ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="courierStatusFilter = ''; fetchData();">
                    All
                </button>

                <button v-for="a in analytics" :key="a.status" type="button" class="btn btn-sm"
                    :class="courierStatusFilter === a.status ? 'btn-primary' : 'btn-outline-primary'"
                    @click="courierStatusFilter = a.status; fetchData();">
                    @{{ formatStatus(a.status) }}
                    <span class="badge ms-1">@{{ a.count }}</span>
                </button>
            </div>

            {{-- Loading spinner --}}
            <div v-show="loading" class="pt-loading">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <p class="mb-0">Loading…</p>
            </div>

            {{-- Table + pagination --}}
            <div v-show="!loading">

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order code</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Consignment ID</th>
                                <th>Latest status</th>
                                <th>Updated at</th>
                                <th>Track</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in orders" :key="row.id">
                                <td><strong>@{{ row.order_code }}</strong></td>
                                <td>@{{ row.customer ? row.customer.name : '—' }}</td>
                                <td>@{{ row.customer_phone || (row.customer ? row.customer.phone : '—') }}</td>
                                <td><code class="small">@{{ getConsignmentId(row) }}</code></td>
                                <td>
                                    <span class="badge" :class="statusBadgeClass(getLatestStatus(row))">
                                        @{{ formatStatus(getLatestStatus(row)) }}
                                    </span>
                                </td>
                                <td class="small text-muted">@{{ getUpdatedAt(row) }}</td>
                                <td>
                                    <a v-if="getTrackUrl(row)" :href="getTrackUrl(row)" target="_blank" rel="noopener"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <span v-else class="text-muted">—</span>
                                </td>
                            </tr>

                            {{-- Empty state --}}
                            <tr v-if="orders.length === 0" class="pt-empty">
                                <td colspan="7">
                                    <i class="fas fa-box-open me-2 opacity-50"></i>
                                    No Pathao orders found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div v-if="pagination.last_page > 1" class="pt-pagination-wrap">
                    <div class="pt-pagination-info">
                        Showing @{{ pagination.from }}–@{{ pagination.to }} of @{{ pagination.total }}
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                                <a class="page-link" href="#" @click.prevent="goPage(pagination.current_page - 1)">←
                                    Prev</a>
                            </li>
                            <li class="page-item" v-for="p in paginationLinks" :key="p"
                                :class="{ active: p === pagination.current_page }">
                                <a class="page-link" href="#" @click.prevent="goPage(p)">@{{ p }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                                <a class="page-link" href="#"
                                    @click.prevent="goPage(pagination.current_page + 1)">Next →</a>
                            </li>
                        </ul>
                    </nav>
                </div>

            </div>{{-- /v-show --}}
        </div>{{-- /card-body --}}
    </div>{{-- /#pathao_courier_status_app --}}
@endsection

@section('footer_js')
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var apiUrl = @json($courierOrdersRoute);

            new Vue({
                el: '#pathao_courier_status_app',
                data: {
                    loading: true,
                    analytics: [],
                    orders: [],
                    pagination: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 15,
                        total: 0,
                        from: 0,
                        to: 0
                    },
                    courierStatusFilter: ''
                },
                computed: {
                    paginationLinks: function() {
                        var cur = this.pagination.current_page;
                        var last = this.pagination.last_page;
                        var from = Math.max(1, cur - 2);
                        var to = Math.min(last, cur + 2);
                        var arr = [];
                        for (var i = from; i <= to; i++) arr.push(i);
                        return arr;
                    }
                },
                mounted: function() {
                    this.fetchData();
                },
                methods: {
                    fetchData: function() {
                        var vm = this;
                        vm.loading = true;
                        var qs = '?page=' + (vm.pagination.current_page || 1);
                        if (vm.courierStatusFilter) qs += '&courier_status=' + encodeURIComponent(vm
                            .courierStatusFilter);
                        fetch(apiUrl + qs, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(function(r) {
                            return r.json();
                        }).then(function(res) {
                            vm.loading = false;
                            vm.analytics = res.analytics || [];
                            var data = res.data;
                            if (data && data.data) {
                                vm.orders = data.data;
                                vm.pagination = {
                                    current_page: data.current_page,
                                    last_page: data.last_page,
                                    per_page: data.per_page,
                                    total: data.total,
                                    from: data.from,
                                    to: data.to
                                };
                            } else {
                                vm.orders = [];
                            }
                        }).catch(function() {
                            vm.loading = false;
                            vm.orders = [];
                        });
                    },
                    goPage: function(p) {
                        if (p < 1 || p > this.pagination.last_page) return;
                        this.pagination.current_page = p;
                        this.fetchData();
                    },
                    getConsignmentId: function(row) {
                        return (row.courier_info && row.courier_info.consignment_id) ? row.courier_info
                            .consignment_id : '—';
                    },
                    getLatestStatus: function(row) {
                        return (row.courier_info && row.courier_info.status) ? row.courier_info.status :
                            '—';
                    },
                    getUpdatedAt: function(row) {
                        if (!row.courier_info || !row.courier_info.updated_at) return '—';
                        var d = row.courier_info.updated_at;
                        if (d.length >= 16) return d.slice(0, 16).replace('T', ' ');
                        return d;
                    },
                    getTrackUrl: function(row) {
                        return (row.courier_info && row.courier_info.pathao_status_url) ? row
                            .courier_info.pathao_status_url : null;
                    },
                    formatStatus: function(s) {
                        if (!s || s === '—') return s;
                        return s.split('-').map(function(w) {
                            return w.charAt(0).toUpperCase() + w.slice(1);
                        }).join(' ');
                    },

                    statusBadgeClass(status) {
                        const map = {
                            pending: 'badge-status-pending',
                            confirmed: 'badge-status-confirmed',
                            picked: 'badge-status-picked',
                            delivered: 'badge-status-delivered',
                            cancelled: 'badge-status-cancelled',
                            returned: 'badge-status-returned',
                            on_hold: 'badge-status-on_hold',
                            in_transit: 'badge-status-in_transit',
                            partially_delivered: 'badge-status-partially_delivered',
                        };
                        // normalise: lowercase + replace spaces/dashes with underscores
                        const key = (status || '').toLowerCase().replace(/[\s-]+/g, '_');
                        return map[key] || 'bg-secondary';
                    }
                }
            });


        });
    </script>
@endsection
