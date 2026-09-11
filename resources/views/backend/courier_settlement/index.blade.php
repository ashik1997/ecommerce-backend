@extends('backend.master')

@section('page_title', 'Courier Settlement')
@section('page_heading', 'Courier Settlement')

@section('content')
    @php
        $isDeliveryContext = request()->routeIs('delivery-management.*');
        $ordersRoute = $isDeliveryContext ? route('delivery-management.courier-settlements.orders') : route('courier-settlements.orders');
        $syncRoute = $isDeliveryContext ? route('delivery-management.courier-settlements.sync') : route('courier-settlements.sync');
        $storeRoute = $isDeliveryContext ? route('delivery-management.courier-settlements.store') : route('courier-settlements.store');
    @endphp
    @if ($isDeliveryContext)
        @include('backend.delivery_management.partials.nav')
    @endif
    <div id="courier_settlement_app" class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-0"><i class="feather-truck"></i> Courier Settlement</h5>
                <small class="text-muted">Collect COD from courier, post delivery expense, and close receivables.</small>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label>Courier</label>
                    <select class="form-control" v-model="filters.courier">
                        <option value="">Select Courier</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ strtolower($courier->title) }}">{{ $courier->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Deposit Account</label>
                    <select class="form-control" v-model="settlement.account_id">
                        <option value="">Select Account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_name }} ({{ number_format($account->balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Settlement Amount</label>
                    <input type="number" min="0" step="0.01" class="form-control" v-model.number="settlement.amount">
                </div>
                <div class="col-md-2">
                    <label>Status Filter</label>
                    <select class="form-control" v-model="filters.status">
                        <option value="">All</option>
                        <option value="success">Success</option>
                        <option value="partial_return">Partial Return</option>
                        <option value="full_return">Full Return</option>
                        <option value="cancel_not_received">Cancel / Not Received</option>
                        <option value="pending">Pending</option>
                        <option value="unknown">Unknown</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" @click="loadOrders" :disabled="loading || !filters.courier">Load</button>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-info btn-sm" @click="syncSelected" :disabled="loading || selectedIds.length === 0">Sync Selected Status</button>
                <button class="btn btn-outline-secondary btn-sm" @click="selectSettleReady">Select Settle Ready</button>
                <button class="btn btn-outline-secondary btn-sm" @click="clearSelection">Clear</button>
            </div>

            <div class="row mt-3">
                <div class="col-md-3"><div class="alert alert-light border mb-2">Orders: <strong>@{{ selectedRows.length }}</strong></div></div>
                <div class="col-md-3"><div class="alert alert-light border mb-2">Receivable: <strong>@{{ money(selectedTotals.receivable) }}</strong></div></div>
                <div class="col-md-3"><div class="alert alert-light border mb-2">Courier Expense: <strong>@{{ money(selectedTotals.expense) }}</strong></div></div>
                <div class="col-md-3"><div class="alert alert-light border mb-2">Receive: <strong>@{{ money(selectedTotals.received) }}</strong></div></div>
            </div>

            <div class="table-responsive mt-2">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th><input type="checkbox" :checked="allSelected" @change="toggleAll($event)"></th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Tracking</th>
                            <th>Status</th>
                            <th class="text-end">COD</th>
                            <th class="text-end">Courier Cost</th>
                            <th class="text-end">Receive</th>
                            <th class="text-end">Return Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in filteredOrders" :key="row.id">
                            <td><input type="checkbox" v-model="row.selected" :disabled="!canSettle(row)"></td>
                            <td>
                                <strong>@{{ row.order_code }}</strong><br>
                                <small class="text-muted">@{{ row.shipping_date || '-' }}</small>
                            </td>
                            <td>@{{ row.customer_name || '-' }}<br><small>@{{ row.customer_phone || '-' }}</small></td>
                            <td><code>@{{ row.tracking_id || '-' }}</code></td>
                            <td>
                                <select class="form-control form-control-sm" v-model="row.normalized_status" @change="applyStatusDefaults(row)">
                                    <option value="success">Success</option>
                                    <option value="partial_return">Partial Return</option>
                                    <option value="full_return">Full Return</option>
                                    <option value="cancel_not_received">Cancel / Not Received</option>
                                    <option value="pending">Pending</option>
                                    <option value="unknown">Unknown</option>
                                </select>
                                <small class="text-muted">@{{ row.raw_status }}</small>
                            </td>
                            <td class="text-end">@{{ money(row.cod_amount) }}</td>
                            <td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" v-model.number="row.delivery_cost"></td>
                            <td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" v-model.number="row.received_amount" :disabled="row.normalized_status === 'pending'"></td>
                            <td><input type="number" min="0" step="1" class="form-control form-control-sm text-end" v-model.number="row.returned_qty" :disabled="!['partial_return','full_return','cancel_not_received'].includes(row.normalized_status)"></td>
                        </tr>
                        <tr v-if="!filteredOrders.length">
                            <td colspan="9" class="text-center text-muted py-4">No unsettled orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <label>Settlement Note</label>
                <textarea class="form-control" rows="2" v-model="settlement.note"></textarea>
            </div>

            <div class="mt-3 text-end">
                <button class="btn btn-success" @click="postSettlement" :disabled="posting || selectedRows.length === 0 || !settlement.account_id">
                    Post Settlement
                </button>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = "{{ csrf_token() }}";

        new Vue({
            el: '#courier_settlement_app',
            data: {
                loading: false,
                posting: false,
                filters: { courier: '', status: '' },
                settlement: { account_id: '', amount: 0, note: '' },
                orders: [],
            },
            computed: {
                filteredOrders: function() {
                    var status = this.filters.status;
                    if (!status) return this.orders;
                    return this.orders.filter(function(row) { return row.normalized_status === status; });
                },
                selectedRows: function() {
                    return this.orders.filter(function(row) { return row.selected; });
                },
                selectedIds: function() {
                    return this.selectedRows.map(function(row) { return row.id; });
                },
                allSelected: function() {
                    return this.filteredOrders.length > 0 && this.filteredOrders.every(function(row) { return row.selected || !['success','partial_return','full_return','cancel_not_received'].includes(row.normalized_status); });
                },
                selectedTotals: function() {
                    return this.selectedRows.reduce(function(sum, row) {
                        sum.receivable += parseFloat(row.cod_amount) || 0;
                        sum.expense += parseFloat(row.delivery_cost) || 0;
                        sum.received += parseFloat(row.received_amount) || 0;
                        return sum;
                    }, { receivable: 0, expense: 0, received: 0 });
                }
            },
            methods: {
                money: function(v) {
                    return '৳ ' + Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                canSettle: function(row) {
                    return ['success', 'partial_return', 'full_return', 'cancel_not_received'].includes(row.normalized_status);
                },
                loadOrders: function() {
                    var vm = this;
                    vm.loading = true;
                    axios.get(@json($ordersRoute), { params: vm.filters })
                        .then(function(res) {
                            vm.orders = (res.data.data || []).map(function(row) {
                                row.selected = false;
                                row.received_amount = row.suggested_received_amount || 0;
                                row.returned_qty = ['full_return', 'cancel_not_received'].includes(row.normalized_status) ? row.total_qty : 0;
                                return row;
                            });
                        })
                        .finally(function() { vm.loading = false; });
                },
                applyStatusDefaults: function(row) {
                    if (row.normalized_status === 'success') {
                        row.received_amount = Math.max(0, (parseFloat(row.cod_amount) || 0) - (parseFloat(row.delivery_cost) || 0));
                        row.returned_qty = 0;
                    }
                    if (['full_return', 'cancel_not_received'].includes(row.normalized_status)) {
                        row.received_amount = 0;
                        row.returned_qty = row.total_qty || 0;
                    }
                },
                selectSettleReady: function() {
                    this.orders.forEach(function(row) { row.selected = ['success', 'partial_return', 'full_return', 'cancel_not_received'].includes(row.normalized_status); });
                },
                clearSelection: function() {
                    this.orders.forEach(function(row) { row.selected = false; });
                },
                toggleAll: function(e) {
                    var checked = e.target.checked;
                    this.filteredOrders.forEach(function(row) {
                        if (['success', 'partial_return', 'full_return', 'cancel_not_received'].includes(row.normalized_status)) row.selected = checked;
                    });
                },
                syncSelected: function() {
                    var vm = this;
                    vm.loading = true;
                    axios.post(@json($syncRoute), { order_ids: vm.selectedIds })
                        .then(function(res) {
                            var byId = {};
                            (res.data.data || []).forEach(function(row) { byId[row.id] = row; });
                            vm.orders = vm.orders.map(function(row) {
                                if (!byId[row.id]) return row;
                                return Object.assign(row, byId[row.id], { selected: row.selected });
                            });
                        })
                        .finally(function() { vm.loading = false; });
                },
                postSettlement: function() {
                    var vm = this;
                    if (!confirm('Post this courier settlement?')) return;
                    vm.posting = true;
                    axios.post(@json($storeRoute), {
                        courier: vm.filters.courier,
                        account_id: vm.settlement.account_id,
                        settlement_amount: vm.settlement.amount || vm.selectedTotals.received,
                        note: vm.settlement.note,
                        orders: vm.selectedRows.map(function(row) {
                            return {
                                order_id: row.id,
                                normalized_status: row.normalized_status,
                                received_amount: row.received_amount || 0,
                                courier_cost: row.delivery_cost || 0,
                                returned_qty: row.returned_qty || 0,
                            };
                        })
                    }).then(function(res) {
                        window.location.href = res.data.redirect;
                    }).catch(function(err) {
                        alert((err.response && err.response.data && err.response.data.message) || 'Settlement failed');
                    }).finally(function() { vm.posting = false; });
                }
            }
        });
    </script>
@endsection
