@extends('backend.master')

@section('header_css')
    <style>
        .order_management_wrapper {
            --om-brand: #0d9488;
            --om-brand-light: #ccfbf1;
        }

        .table tbody tr {
            border: 1px solid #ededed;

            &:hover {
                background: #fafbff;
            }

            &:has(input[type="checkbox"]:checked) {
                background: #f5f6ff;
                border-left: 3px solid #3b4cca;
            }
        }
    </style>
@endsection

@section('page_title')
    Order Management
@endsection
@section('page_heading')
    All Orders
@endsection

@section('content')
    <div id="order_management_wrapper" class="order_management_wrapper">
        {{-- Full-screen loader (prevents double submit on slow network) --}}
        <div v-show="fullScreenLoading" style="display: flex;"
            class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center"
            style="background: rgba(0,0,0,0.5); z-index: 9999;">
            <div class="text-center text-white">
                <div class="spinner-border mb-2" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden"></span>
                </div>
                <div>@{{ fullScreenLoadingText }}</div>
            </div>
        </div>

        {{-- Pathao bulk orders modal (full-screen overlay, centered table + button) --}}
        <div v-show="showPathaoBulkModal" :class="{ 'pathao_bulk_modal_open': showPathaoBulkModal }"
            class="position-fixed pathao_bulk_modal top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3"
            style="background: rgba(0,0,0,0.5); z-index: 9998;">
            <div class="bg-white rounded shadow-lg w-100"
                style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i> Add orders to Pathao courier</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="closePathaoBulkModal"
                        aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-3 overflow-auto flex-grow-1">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order code</th>
                                <th>Customer name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th class="text-end">Grand total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in getSelectedOrders()" :key="row.id">
                                <td>@{{ row.order_code }}</td>
                                <td>@{{ row.customer_name }}</td>
                                <td>@{{ row.customer_phone }}</td>
                                <td class="small">@{{ row.address || '—' }}</td>
                                <td class="text-end">৳@{{ formatNumber(row.grand_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-light">
                    <button type="button" class="btn btn-primary" @click="doPathaoBulkSubmit">
                        <i class="fas fa-paper-plane me-1"></i> Add orders into Pathao courier
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2"
                        @click="closePathaoBulkModal">Cancel</button>
                </div>
            </div>
        </div>

        {{-- Steadfast bulk orders modal (same pattern as Pathao) --}}
        <div v-show="showSteadfastBulkModal" :class="{ 'pathao_steadfast_modal_open': showSteadfastBulkModal }"
            class="position-fixed pathao_bulk_modal top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3"
            style="background: rgba(0,0,0,0.5); z-index: 9998;">
            <div class="bg-white rounded shadow-lg w-100"
                style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i> Add orders to Steadfast courier</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeSteadfastBulkModal"
                        aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-3 overflow-auto flex-grow-1">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order code</th>
                                <th>Customer name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th class="text-end">Grand total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in getSelectedOrders()" :key="row.id">
                                <td>@{{ row.order_code }}</td>
                                <td>@{{ row.customer_name }}</td>
                                <td>@{{ row.customer_phone }}</td>
                                <td class="small">@{{ row.address || '—' }}</td>
                                <td class="text-end">৳@{{ formatNumber(row.grand_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-light">
                    <button type="button" class="btn btn-primary" @click="doSteadfastBulkSubmit">
                        <i class="fas fa-paper-plane me-1"></i> Add orders into Steadfast courier
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2"
                        @click="closeSteadfastBulkModal">Cancel</button>
                </div>
            </div>
        </div>

        {{-- CarryBee bulk orders modal (same pattern as Pathao) --}}
        <div v-show="showCarryBeeBulkModal" :class="{ 'carrybee_modal_open': showCarryBeeBulkModal }"
            class="position-fixed carrybee_bulk_modal top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3"
            style="background: rgba(0,0,0,0.5); z-index: 9998;">
            <div class="bg-white rounded shadow-lg w-100"
                style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i> Add orders to CarryBee courier</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeCarryBeeBulkModal"
                        aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-3 overflow-auto flex-grow-1">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order code</th>
                                <th>Customer name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th class="text-end">Grand total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in getSelectedOrders()" :key="row.id">
                                <td>@{{ row.order_code }}</td>
                                <td>@{{ row.customer_name }}</td>
                                <td>@{{ row.customer_phone }}</td>
                                <td class="small">@{{ row.address || '—' }}</td>
                                <td class="text-end">৳@{{ formatNumber(row.grand_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-light">
                    <button type="button" class="btn btn-primary" @click="doCarryBeeBulkSubmit">
                        <i class="fas fa-paper-plane me-1"></i> Add orders into CarryBee courier
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2"
                        @click="closeCarryBeeBulkModal">Cancel</button>
                </div>
            </div>
        </div>

        {{-- Top analytics cards --}}
        <div class="row g-2 mb-3" v-if="analytics">
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': activeFilter === 'all' }" @click="setFilter('all')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-shopping-cart fa-lg sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">All (@{{ analytics.all ? analytics.all.count : 0 }})</div>
                            <div class="stat-total text-success text-left">৳@{{ formatNumber(analytics.all ? analytics.all.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': activeFilter === 'pending' }" @click="setFilter('pending')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-clock fa-lg text-warning sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Pending (@{{ analytics.pending ? analytics.pending.count : 0 }})</div>
                            <div class="stat-total text-warning text-left">৳@{{ formatNumber(analytics.pending ? analytics.pending.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': activeFilter === 'invoiced' }" @click="setFilter('invoiced')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-file-invoice fa-lg text-info sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Invoiced (@{{ analytics.invoiced ? analytics.invoiced.count : 0 }})</div>
                            <div class="stat-total text-info text-left">৳@{{ formatNumber(analytics.invoiced ? analytics.invoiced.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': activeFilter === 'delivered' }" @click="setFilter('delivered')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-truck fa-lg text-success sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Delivered (@{{ analytics.delivered ? analytics.delivered.count : 0 }})</div>
                            <div class="stat-total text-success text-left">৳@{{ formatNumber(analytics.delivered ? analytics.delivered.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': activeFilter === 'canceled' }" @click="setFilter('canceled')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-times-circle fa-lg text-secondary sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Canceled (@{{ analytics.canceled ? analytics.canceled.count : 0 }})</div>
                            <div class="stat-total text-secondary text-left">৳@{{ formatNumber(analytics.canceled ? analytics.canceled.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': activeFilter === 'returned' }" @click="setFilter('returned')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-undo fa-lg text-orange sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Returned (@{{ analytics.returned ? analytics.returned.count : 0 }})</div>
                            <div class="stat-total text-orange text-left">৳@{{ formatNumber(analytics.returned ? analytics.returned.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-shipping-fast fa-lg text-primary sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Couriered (@{{ analytics.couriered ? analytics.couriered.count : 0 }})</div>
                            <div class="stat-total text-primary text-left">৳@{{ formatNumber(analytics.couriered ? analytics.couriered.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': paidStatusFilter === 'paid' }" @click="setPaidFilter('paid')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-check-circle fa-lg text-success sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Paid (@{{ analytics.paid ? analytics.paid.count : 0 }})</div>
                            <div class="stat-total text-success text-left">৳@{{ formatNumber(analytics.paid ? analytics.paid.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-0 shadow-sm om-stat-card"
                    :class="{ 'om-stat-active': paidStatusFilter === 'due' }" @click="setPaidFilter('due')">
                    <div class="card-body p-3 text-center d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-money-bill-wave fa-lg text-danger sales_analytics_icon"></i>
                        <div>
                            <div class="stat-label text-muted text-left">Due (@{{ analytics.due ? analytics.due.count : 0 }})</div>
                            <div class="stat-total text-danger text-left">৳@{{ formatNumber(analytics.due ? analytics.due.total_value : 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order filter: search always visible + advanced collapse --}}
        <div class="card border-0 shadow-sm bg-white mb-3">
            <div class="card-body py-3 px-0">
                <div class="d-flex align-items-center gap-2 justify-content-between">
                    <div class="col-12 col-md-6 col-lg-4">
                        <input type="text" class="form-control form-control-sm"
                            placeholder="Customer, phone, ID, order code, creator..." v-model="filters.search"
                            @input="debouncedFetch">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="collapse"
                            data-target="#omAdvancedFilter" aria-expanded="false">
                            <i class="fas fa-filter"></i> filter
                        </button>
                    </div>
                </div>
                <div class="collapse p-2" id="omAdvancedFilter">
                    <hr class="my-2">
                    <div class="row g-2">
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Order status</label>
                            <select class="form-control form-select-sm" v-model="filters.order_status"
                                @change="fetchData">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="accepted">Accepted</option>
                                <option value="processing">Processing</option>
                                <option value="invoiced">Invoiced</option>
                                <option value="delivered">Delivered</option>
                                <option value="canceled">Canceled</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Discount type</label>
                            <select class="form-control form-select-sm" v-model="filters.discount_type"
                                @change="fetchData">
                                <option value="">All</option>
                                <option value="percent">Percent</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Payment type</label>
                            <select class="form-control form-select-sm" v-model="filters.payment_type"
                                @change="fetchData">
                                <option value="">All</option>
                                @foreach ($paymentTypes ?? [] as $pt)
                                    <option value="{{ $pt->id }}">{{ $pt->payment_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Customer source</label>
                            <select class="form-control form-select-sm" v-model="filters.customer_source_type"
                                @change="fetchData">
                                <option value="">All</option>
                                @foreach ($customerSources ?? [] as $cs)
                                    <option value="{{ $cs->id }}">{{ $cs->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Warehouse</label>
                            <select class="form-control form-select-sm" v-model="filters.warehouse_id"
                                @change="fetchData">
                                <option value="">All</option>
                                @foreach ($warehouses ?? [] as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Warehouse status</label>
                            <select class="form-control form-select-sm" v-model="filters.warehouse_status"
                                @change="fetchData">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Sales date from</label>
                            <input type="date" class="form-control form-control-sm" v-model="filters.sales_date_from"
                                @change="fetchData">
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Sales date to</label>
                            <input type="date" class="form-control form-control-sm" v-model="filters.sales_date_to"
                                @change="fetchData">
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Due date from</label>
                            <input type="date" class="form-control form-control-sm" v-model="filters.due_date_from"
                                @change="fetchData">
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Due date to</label>
                            <input type="date" class="form-control form-control-sm" v-model="filters.due_date_to"
                                @change="fetchData">
                        </div>
                        <div class="col-6 col-md-4 col-lg-3 pb-2">
                            <label class="form-label small mb-0 block">Paid status</label>
                            <select class="form-control form-select-sm" v-model="filters.paid_status"
                                @change="fetchData">
                                <option value="">All</option>
                                <option value="paid">Paid</option>
                                <option value="due">Due</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick actions (selected items) --}}
        <div class="order_quick_actions_wrapper">
            <div class="button_container">
                <span class="small text-muted">@{{ selectedIds.length }} orders selected</span>
                <select class="oder_action_select" style="width: auto;" v-model="quickActionStatus"
                    @change="quickChangeStatus">
                    <option value="">Change status</option>
                    <option value="pending">Pending</option>
                    <option value="invoiced">Invoiced</option>
                    <option value="delivered">Delivered</option>
                    <option value="canceled">Canceled</option>
                </select>
                <select class="oder_action_select" style="width: auto;" v-model="quickCourier"
                    @change="quickAddCourier">
                    <option value="">Add to courier</option>
                    @foreach (App\Models\ProductOrderCourierMethod::get() as $courier)
                        <option value="{{ $courier->title }}">{{ $courier->title }}</option>
                    @endforeach
                </select>
                <button type="button" class="button" @click="quickPrint">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <button type="button" class="button" @click="quickEmail">
                    <i class="fas fa-envelope"></i>
                    Email
                </button>
                {{-- <button type="button" class="button" @click="quickSms">
                    <i class="fas fa-sms"></i> 
                    SMS
                </button> --}}
                <a href="{{ url('/pos/desktop') }}" class="button">
                    <i class="fas fa-plus"></i>
                    Add Order
                </a>
            </div>
        </div>

        {{-- Table --}}
        <div class="order_list_page">
            <div class="table-wrap">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body p-0">
                        <div class="table-responsive product_order_list_table">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">
                                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                                title="Select all">
                                        </th>
                                        <th class="order">
                                            Order
                                        </th>
                                        <th class="customer">Customer</th>
                                        <th class="warehouse">Products</th>
                                        <th class="total">Total</th>
                                        <th class="status">Status</th>
                                        <th class="delivery">Delivery</th>
                                        <th class="action">Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-if="loading">
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </td>
                                    </tr>
                                    <tr v-else-if="!orders.length">
                                        <td colspan="9" class="text-center py-4 text-muted">No orders found.</td>
                                    </tr>

                                    <tr v-else v-for="row in orders" :key="row.id">

                                        <!-- Checkbox -->
                                        <td>
                                            <input type="checkbox" :value="row.id" v-model="selectedIds">
                                        </td>

                                        <!-- Order No + Dates + Icons -->
                                        <td class="order">
                                            <a :href="editUrl(row.id)" class="order-id">@{{ row.order_code }}</a>
                                            <div class="date-line">
                                                <span class="icon">📅</span> @{{ row.created_at }}
                                            </div>
                                            <div class="date-line" v-if="row.due_date">
                                                <span class="icon">🚚</span> @{{ row.shipping_date }}
                                            </div>
                                            <div class="order-icons">
                                                <div class="icon-mini" title="Copy code"
                                                    @click.prevent="copyCode(row.order_code)">⎘</div>
                                                <a :href="invoiceUrl(row.slug)" target="_blank" class="icon-mini"
                                                    title="Print Invoice">🖨</a>
                                                <div class="icon-mini" title="More">⋯</div>
                                            </div>
                                        </td>

                                        <!-- Customer -->
                                        <td class="customer">
                                            <div class="cust-name">
                                                @{{ row.customer_name }}
                                                <span class="pct-badge pct-badge--0"
                                                    v-if="row.order_source">@{{ row.order_source }}</span>
                                            </div>
                                            <div class="cust-phone">@{{ row.customer_phone }}</div>
                                            <div class="cust-addr">@{{ row.address || '—' }}</div>
                                            <div class="order-icons" style="margin-top: 6px;">
                                                <a v-if="row.customer_phone" :href="'tel:' + row.customer_phone"
                                                    class="icon-mini" title="Call">📞</a>
                                                <a v-if="row.customer_email" :href="'mailto:' + row.customer_email"
                                                    class="icon-mini" title="Email">✉️</a>
                                            </div>
                                        </td>

                                        <!-- Warehouse -->
                                        <td class="warehouse">
                                            <div class="order-products-mini">
                                                <div v-for="product in row.products" :key="product.id"
                                                    class="order-products-mini__item" :title="product.product_name">
                                                    <div class="order-products-mini__thumb">
                                                        <img :src="file_base_url + '/' + product.image" alt="">
                                                    </div>
                                                    <div class="order-products-mini__meta">
                                                        <div class="order-products-mini__title">@{{ product.product_name }}
                                                        </div>
                                                        <div class="order-products-mini__qty">Qty: @{{ product.qty }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- <span class="wh-tag">@{{ row.warehouse_name || '' }}</span> --}}
                                        </td>

                                        <!-- Totals -->
                                        <td class="total">
                                            <div class="totals" style="width: 200px;">
                                                <div class="total-row">
                                                    <span class="lbl">Subtotal</span>
                                                    <span class="amt amt--main">৳@{{ formatNumber(row.grand_total) }}</span>
                                                </div>
                                                <div class="total-row" v-if="row.shipping_charge > 0">
                                                    <span class="lbl">Delivery</span>
                                                    <span class="amt">৳@{{ formatNumber(row.shipping_charge) }}</span>
                                                </div>
                                                <div class="total-row" v-else>
                                                    <span class="lbl">Delivery</span>
                                                    <span class="amt amt--free">৳0</span>
                                                </div>
                                                <div class="total-row">
                                                    <span class="lbl">Total</span>
                                                    <span class="amt">৳@{{ formatNumber(row.grand_total) }}</span>
                                                </div>
                                                <div class="total-row" v-if="!row.is_ecommerce_unaccepted">
                                                    <span class="lbl">@{{ row.order_source === 'ecommerce' ? 'Received' : 'Paid' }}</span>
                                                    <span class="amt amt--free">৳@{{ formatNumber(row.display_paid_amount) }}</span>
                                                </div>
                                                <div class="total-row" v-if="row.show_due_amount">
                                                    <span class="lbl">@{{ row.order_source === 'ecommerce' ? 'Courier Receivable' : 'Due' }}</span>
                                                    <span class="amt amt--due">৳@{{ formatNumber(row.display_due_amount) }}</span>
                                                </div>
                                                <div class="total-row" v-if="row.is_ecommerce_unaccepted">
                                                    <span class="lbl">Finance</span>
                                                    <span class="amt amt--due">Not accepted</span>
                                                </div>
                                                <template v-if="row.order_source === 'ecommerce' && row.is_finance_eligible">
                                                    <div class="total-row">
                                                        <span class="lbl">COD</span>
                                                        <span class="amt">৳@{{ formatNumber(row.courier_cod_amount) }}</span>
                                                    </div>
                                                    <div class="total-row">
                                                        <span class="lbl">Courier</span>
                                                        <span class="amt">৳@{{ formatNumber(row.courier_delivery_cost) }}</span>
                                                    </div>
                                                    <div class="total-row">
                                                        <span class="lbl">@{{ row.settled_from_courier ? 'Settled' : 'Receivable' }}</span>
                                                        <span class="amt"
                                                            :class="{ 'amt--due': !row.settled_from_courier, 'amt--free': row.settled_from_courier }">
                                                            ৳@{{ formatNumber(row.courier_receivable_amount) }}
                                                        </span>
                                                    </div>
                                                    <div class="total-row" v-if="row.courier_settlement_date">
                                                        <span class="lbl">Settlement</span>
                                                        <span class="amt">@{{ row.courier_settlement_date }}</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="status">
                                            <div class="status-group">
                                                <div>
                                                    <span class="status-label">Order</span>
                                                    <span class="wh-tag text-dark"
                                                        :class="['badge-status', statusBadgeClass(row.order_status)]">
                                                        @{{ row.order_status }}
                                                    </span>
                                                </div>
                                                <div v-if="row.is_couriered">
                                                    <span class="status-label">Courier</span>

                                                    <span class="wh-tag text-dark" class="badge-couriered">
                                                        @{{ row.delivery_info?.courier_method || '—' }}
                                                    </span>

                                                    <a :href="row.courier_info?.pathao_status_url" target="_blank"
                                                        class="wh-tag mt-1 d-block text-dark" title="Track Courier">
                                                        <i class="fas fa-external-link-alt"></i> Track Courier
                                                    </a>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Delivery -->
                                        <td class="delivery">
                                            <span v-if="row.is_couriered && row.delivery_info?.delivery_method"
                                                class="delivery-tag delivery-tag--home">
                                                @{{ row.delivery_info?.delivery_method || '—' }}
                                            </span>
                                            <div class="fraud-info" v-if="row.fraud_info">
                                                <div class="fraud-badge"
                                                    :class="'fraud-badge--' + row.fraud_info.status.toLowerCase()">
                                                    <i class="fas fa-shield-alt"></i>
                                                    <span>@{{ row.fraud_info.status }}</span>
                                                    <span class="fraud-badge__score">@{{ row.fraud_info.score }}</span>

                                                    <div class="fraud-popup">
                                                        <div class="fraud-popup__head">
                                                            <span class="fraud-popup__phone">
                                                                <i class="fas fa-phone-alt"></i> @{{ row.fraud_info.phone }}
                                                            </span>
                                                            <span class="fraud-popup__status"
                                                                :class="'fraud-popup__status--' + row.fraud_info.status
                                                                    .toLowerCase()">
                                                                @{{ row.fraud_info.status }}
                                                            </span>
                                                        </div>
                                                        <div class="fraud-popup__stats">
                                                            <div class="fraud-stat">
                                                                <span
                                                                    class="fraud-stat__val">@{{ row.fraud_info.total_parcel }}</span>
                                                                <span class="fraud-stat__lbl">Total</span>
                                                            </div>
                                                            <div class="fraud-stat fraud-stat--ok">
                                                                <span
                                                                    class="fraud-stat__val">@{{ row.fraud_info.success_parcel }}</span>
                                                                <span class="fraud-stat__lbl">Delivered</span>
                                                            </div>
                                                            <div class="fraud-stat fraud-stat--bad">
                                                                <span
                                                                    class="fraud-stat__val">@{{ row.fraud_info.cancel_parcel }}</span>
                                                                <span class="fraud-stat__lbl">Cancelled</span>
                                                            </div>
                                                        </div>
                                                        <div class="fraud-popup__couriers" v-if="row.fraud_info.response">
                                                            <div class="fraud-courier-row"
                                                                v-for="(info, name) in row.fraud_info.response"
                                                                :key="name">
                                                                <span
                                                                    class="fraud-courier-row__name">@{{ name }}</span>
                                                                <div class="fraud-courier-row__bar">
                                                                    <div class="fraud-courier-row__fill"
                                                                        :style="{ width: info.data.deliveredPercentage + '%' }">
                                                                    </div>
                                                                </div>
                                                                <span
                                                                    class="fraud-courier-row__pct">@{{ info.data.deliveredPercentage }}%</span>
                                                                <span
                                                                    class="fraud-courier-row__count">@{{ info.data.success }}/@{{ info.data.total }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="fraud-popup__footer">
                                                            <i class="fas fa-database"></i> @{{ row.fraud_info.source }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="action">
                                            <div class="actions">

                                                <a v-if="row.order_source === 'ecommerce'" :href="editUrl(row.id)"
                                                    class="btn-action btn-action--edit" title="Edit">✏️</a>

                                                <a :href="`/product-order/${row.id}/courier`"
                                                    class="btn-action btn-action--copy" title="Courier">🚚</a>

                                                <a v-if="row.order_source === 'pos'" :href="posInvoiceUrl(row.slug)"
                                                    target="_blank" class="btn-action btn-action--view"
                                                    title="Pos Invoice / Print">🖨</a>

                                                <a v-if="row.order_source === 'ecommerce'" :href="invoiceUrl(row.slug)"
                                                    target="_blank" class="btn-action btn-action--view"
                                                    title="Invoice / Print">🖨</a>

                                                <a v-if="row.order_status === 'invoiced' || row.order_status === 'delivered'"
                                                    :href="`/create/product-order-return/${row.slug}`"
                                                    class="btn-action btn-action--copy" title="Create Return">↩</a>

                                                <button v-if="row.order_status === 'pending'" type="button"
                                                    class="btn-action btn-action--del" title="Delete"
                                                    @click="confirmDelete(row)">✕</button>
                                            </div>
                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="order_info && order_info.links && order_info.links.length > 0">
                            <div class="tbl-footer">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span>
                                        Showing <strong>@{{ order_info.from }}–@{{ order_info.to }}</strong>
                                        of <strong>@{{ order_info.total }}</strong> orders
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">
                                            Page @{{ order_info.current_page }} of @{{ order_info.last_page }}
                                            (total
                                            @{{ order_info.total }})
                                        </span>
                                        <select class="form-select form-select-sm" style="width: auto;" v-model="perPage"
                                            @change="fetchData">
                                            <option :value="10">10</option>
                                            <option :value="50">50</option>
                                            <option :value="100">100</option>
                                            <option :value="200">200</option>
                                        </select>
                                        <span class="text-muted">per page</span>
                                    </div>
                                </div>

                                <div class="pages">
                                    <template v-for="link in order_info.links" :key="link.label">
                                        <button class="page-btn" :class="{ active: link.active }" :disabled="!link.url"
                                            @click="link.url && goToPage(link.url)" v-html="link.label">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Pagination --}}
                        {{-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 border-top">
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted">
                                    Page @{{ order_info.current_page }} of @{{ order_info.last_page }}
                                    (total
                                    @{{ order_info.total }})
                                </span>
                                <select class="form-select form-select-sm" style="width: auto;" v-model="perPage"
                                    @change="fetchData">
                                    <option :value="10">10</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                    <option :value="200">200</option>
                                </select>
                                <span class="small text-muted">per page</span>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    @click="goPage(pagination.current_page + 1)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var orderListUrl = @json(route('OrderListPage'));
            var posDesktopBase = @json(route('pos.desktop.index'));
            var invoiceBase = @json(url('order-invoice'));
            var posInvoiceBase = @json(url('pos/desktop/print'));
            var printBase = @json(url('order-invoice'));
            var deleteBase = @json(url('delete/product-order/manage'));
            var quickChangeStatusUrl = @json(route('pos.product-orders.quick-change-status'));
            // var printBulkInvoicesUrl = @json(route('pos.desktop.print-bulk-invoices'));
            var printBulkInvoicesUrl = @json(route('pos.desktop.print-bulk-full-invoices'));
            var emailBulkInvoicesUrl = @json(route('pos.desktop.email-bulk-invoices'));
            var pathaoBulkUrl = @json(route('pathao.set-bulk-orders'));
            var steadfastBulkUrl = @json(route('steadfast.set-bulk-orders'));
            var carryBeeBulkUrl = @json(route('carrybee.set-bulk-orders'));

            new Vue({
                el: '#order_management_wrapper',
                data: {
                    order_info: null,
                    orders: [],
                    analytics: null,
                    file_base_url: "{{ get_file_url() }}",
                    pagination: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0,
                        from: 0,
                        to: 0
                    },
                    loading: false,
                    filters: {
                        search: '',
                        order_status: '',
                        discount_type: '',
                        payment_type: '',
                        customer_source_type: '',
                        warehouse_id: '',
                        warehouse_status: '',
                        sales_date_from: '',
                        sales_date_to: '',
                        due_date_from: '',
                        due_date_to: '',
                        paid_status: ''
                    },
                    order_source: @json(request('order_source', 'pos')),
                    product_website_id: parseInt({{ request('product_website_id', 0) }}),
                    is_completed: parseInt({{ request('is_completed', 1) }}),
                    activeFilter: '',
                    paidStatusFilter: '',
                    perPage: 10,
                    selectedIds: [],
                    selectAll: false,
                    quickActionStatus: '',
                    quickCourier: '',
                    debounceTimer: null,
                    fullScreenLoading: false,
                    fullScreenLoadingText: 'Updating...',
                    showPathaoBulkModal: false,
                    showSteadfastBulkModal: false,
                    showCarryBeeBulkModal: false

                },
                mounted: function() {
                    this.fetchData();
                },
                watch: {
                    order_source: function(v) {
                        this.fetchData();
                    },
                    product_website_id: function(v) {
                        this.fetchData();
                    }
                },
                methods: {
                    buildParams: function(page) {
                        var p = {
                            page: page || 1,
                            per_page: this.perPage
                        };
                        if (this.order_source) p.order_source = this.order_source;
                        if (this.product_website_id) p.product_website_id = this.product_website_id;
                        if (this.filters.search) p.search = this.filters.search;
                        if (this.filters.order_status) p.order_status = this.filters.order_status;
                        if (this.filters.discount_type) p.discount_type = this.filters.discount_type;
                        if (this.filters.payment_type) p.payment_type = this.filters.payment_type;
                        if (this.filters.customer_source_type) p.customer_source_type = this.filters
                            .customer_source_type;
                        if (this.filters.warehouse_id) p.warehouse_id = this.filters.warehouse_id;
                        if (this.filters.warehouse_status) p.warehouse_status = this.filters
                            .warehouse_status;
                        if (this.filters.sales_date_from) p.sales_date_from = this.filters
                            .sales_date_from;
                        if (this.filters.sales_date_to) p.sales_date_to = this.filters.sales_date_to;
                        if (this.filters.due_date_from) p.due_date_from = this.filters.due_date_from;
                        if (this.filters.due_date_to) p.due_date_to = this.filters.due_date_to;
                        if (this.filters.paid_status) p.paid_status = this.filters.paid_status;
                        if (this.is_completed == 0) p.is_completed = 0;
                        console.log(this.is_completed);

                        return p;
                    },
                    fetchData: function(page) {
                        var vm = this;
                        vm.loading = true;
                        var params = vm.buildParams(page || vm.pagination.current_page);
                        var qs = new URLSearchParams(params).toString();
                        fetch(orderListUrl + '?' + qs, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }).then(function(r) {
                            return r.json();
                        }).then(function(res) {
                            vm.analytics = res.analytics || null;
                            vm.orders = res.data.data || [];
                            vm.order_info = res.data || null;
                            vm.loading = false;
                            this.fullScreenLoading = false;
                        }).catch(function() {
                            vm.loading = false;
                            this.fullScreenLoading = false;
                        });
                    },
                    debouncedFetch: function() {
                        var vm = this;
                        if (vm.debounceTimer) clearTimeout(vm.debounceTimer);
                        vm.debounceTimer = setTimeout(function() {
                            vm.fetchData(1);
                        }, 300);
                    },
                    // checkRow: function(row) {
                    //     console.log('FULL ROW DATA:', row);
                    //     console.table(row);
                    //     alert(JSON.stringify(row, null, 2));
                    // },
                    setFilter: function(status) {
                        // this.activeFilter = this.activeFilter === status ? '' : status;
                        this.activeFilter = status;
                        this.filters.order_status = this.activeFilter;
                        this.paidStatusFilter = '';
                        this.filters.paid_status = '';
                        this.fetchData(1);
                    },
                    setPaidFilter: function(paid) {
                        this.paidStatusFilter = this.paidStatusFilter === paid ? '' : paid;
                        this.filters.paid_status = this.paidStatusFilter;
                        this.fetchData(1);
                    },
                    formatNumber: function(n) {
                        return Number(n).toLocaleString('en-BD', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    },
                    toggleSelectAll: function() {
                        if (this.selectAll) this.selectedIds = this.orders.map(function(o) {
                            return o.id;
                        });
                        else this.selectedIds = [];
                    },
                    copyCode: function(code) {
                        if (navigator.clipboard) navigator.clipboard.writeText(code).then(function() {
                            toastr.success('Copied');
                        });
                    },
                    statusBadgeClass: function(s) {
                        var m = {
                            pending: 'bg-warning',
                            accepted: 'bg-primary',
                            processing: 'bg-primary',
                            invoiced: 'bg-info',
                            delivered: 'bg-success',
                            canceled: 'bg-secondary',
                            cancelled: 'bg-secondary',
                            returned: 'bg-danger'
                        };
                        return m[s] || 'bg-secondary';
                    },
                    editUrl: function(orderId) {
                        // return posDesktopBase + '?order_id=' + orderId;
                        return '/ecommerce/order-edit/' + orderId;
                    },
                    invoiceUrl: function(slug) {
                        return invoiceBase + '/' + slug;
                    },
                    posInvoiceUrl: function(slug) {
                        return posInvoiceBase + '/' + slug;
                    },
                    printUrl: function(slug) {
                        return printBase + '/' + slug + '/pdf';
                    },
                    goPage: function(p) {
                        this.fetchData(p);
                    },
                    quickChangeStatus: function() {
                        if (!this.quickActionStatus) return;
                        if (!this.selectedIds || this.selectedIds.length === 0) {
                            toastr.warning('Please select at least one order.');
                            this.quickActionStatus = '';
                            return;
                        }
                        var vm = this;
                        var status = this.quickActionStatus;
                        var count = this.selectedIds.length;
                        if (typeof Swal === 'undefined') {
                            if (!confirm('Update status to "' + status + '" for ' + count +
                                    ' order(s)?')) {
                                vm.quickActionStatus = '';
                                return;
                            }
                            vm.doBulkStatusUpdate(status);
                            return;
                        }
                        Swal.fire({
                            title: 'Update status?',
                            html: 'Set status to <strong>' + status + '</strong> for <strong>' +
                                count + '</strong> order(s)?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, update',
                            cancelButtonText: 'Cancel'
                        }).then(function(result) {
                            if (result.isConfirmed) vm.doBulkStatusUpdate(status);
                            vm.quickActionStatus = '';
                        });
                    },
                    doBulkStatusUpdate: function(status) {
                        var vm = this;
                        vm.fullScreenLoadingText = 'Updating status...';
                        vm.fullScreenLoading = true;
                        var token = document.querySelector('meta[name="csrf-token"]') && document
                            .querySelector('meta[name="csrf-token"]').getAttribute('content');
                        fetch(quickChangeStatusUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token || ''
                            },
                            body: JSON.stringify({
                                ids: vm.selectedIds,
                                status: status
                            })
                        }).then(function(r) {
                            return r.json();
                        }).then(function(res) {
                            console.log(res);
                            vm.fullScreenLoading = false;
                            if (res.success) {
                                toastr.success(res.message || 'Status updated.');
                                vm.fetchData(vm.pagination.current_page || 1);
                            } else {
                                toastr.error(res.message || 'Update failed.');
                            }
                        }).catch(function() {
                            vm.fullScreenLoading = false;
                            toastr.error('Network error. Please try again.');
                        });
                    },
                    doSteadfastBulkSubmit: function() {
                        var vm = this;
                        vm.fullScreenLoadingText = 'Adding to Steadfast courier...';
                        vm.fullScreenLoading = true;
                        var token = document.querySelector('meta[name="csrf-token"]') && document
                            .querySelector('meta[name="csrf-token"]').getAttribute('content');
                        fetch(steadfastBulkUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token || ''
                            },
                            body: JSON.stringify({
                                ids: vm.selectedIds
                            })
                        }).then(function(r) {
                            return r.json();
                        }).then(function(res) {
                            vm.fullScreenLoading = false;
                            vm.closeSteadfastBulkModal();
                            if (res.success) {
                                toastr.success(res.message || 'Orders sent to Steadfast.');
                                vm.fetchData(vm.pagination.current_page || 1);
                            } else {
                                toastr.error(res.message || 'Steadfast bulk request failed.');
                            }
                        }).catch(function() {
                            vm.fullScreenLoading = false;
                            toastr.error('Network error. Please try again.');
                        });
                    },
                    doCarryBeeBulkSubmit: function() {
                        var vm = this;
                        vm.fullScreenLoadingText = 'Adding to CarryBee courier...';
                        vm.fullScreenLoading = true;
                        var token = document.querySelector('meta[name="csrf-token"]') && document
                            .querySelector('meta[name="csrf-token"]').getAttribute('content');
                        fetch(carryBeeBulkUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token || ''
                            },
                            body: JSON.stringify({
                                ids: vm.selectedIds
                            })
                        }).then(function(r) {
                            console.log(r);
                            return r.json();
                        }).then(function(res) {
                            console.log(res);
                            vm.fullScreenLoading = false;
                            vm.closeCarryBeeBulkModal();
                            if (res.success) {
                                toastr.success(res.message || 'Orders sent to CarryBee.');
                                vm.fetchData(vm.pagination.current_page || 1);
                            } else {
                                toastr.error(res.message || 'CarryBee bulk request failed.');
                            }
                        }).catch(function() {
                            vm.fullScreenLoading = false;
                            toastr.error('Network error. Please try again.');
                        });
                    },
                    quickAddCourier: function() {

                        if (!this.quickCourier) return;
                        if (!this.selectedIds || this.selectedIds.length === 0) {
                            toastr.warning('Please select at least one order.');
                            this.quickCourier = '';
                            return;
                        }
                        if (this.quickCourier === 'Pathao') {
                            this.showPathaoBulkModal = true;
                            return;
                        }
                        if (this.quickCourier === 'Steadfast') {
                            this.showSteadfastBulkModal = true;
                            return;
                        }
                        if (this.quickCourier === 'CarryBee') {
                            this.showCarryBeeBulkModal = true;
                            return;
                        }
                        this.quickCourier = '';
                    },
                    getSelectedOrders: function() {
                        if (!this.orders.length || !this.selectedIds.length) return [];
                        return this.orders.filter(function(o) {
                            return this.selectedIds.indexOf(o.id) !== -1;
                        }, this);
                    },
                    closePathaoBulkModal: function() {
                        this.showPathaoBulkModal = false;
                        this.quickCourier = '';
                    },
                    closeSteadfastBulkModal: function() {
                        this.showSteadfastBulkModal = false;
                        this.quickCourier = '';
                    },
                    closeCarryBeeBulkModal: function() {
                        this.showCarryBeeBulkModal = false;
                        this.quickCourier = '';
                    },
                    doPathaoBulkSubmit: function() {
                        var vm = this;
                        vm.fullScreenLoadingText = 'Adding to Pathao courier...';
                        vm.fullScreenLoading = true;
                        var token = document.querySelector('meta[name="csrf-token"]') && document
                            .querySelector('meta[name="csrf-token"]').getAttribute('content');
                        fetch(pathaoBulkUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token || ''
                            },
                            body: JSON.stringify({
                                ids: vm.selectedIds
                            })
                        }).then(function(r) {
                            return r.json();
                        }).then(function(res) {
                            vm.fullScreenLoading = false;
                            vm.closePathaoBulkModal();
                            if (res.success) {
                                toastr.success(res.message || 'Orders sent to Pathao.');
                                vm.fetchData(vm.pagination.current_page || 1);
                            } else {
                                toastr.error(res.message || 'Pathao bulk request failed.');
                            }
                        }).catch(function() {
                            vm.fullScreenLoading = false;
                            toastr.error('Network error. Please try again.');
                        });
                    },
                    quickPrint: function() {
                        if (!this.selectedIds.length) {
                            toastr.warning('Please select at least one order.');
                            return;
                        }
                        var url = printBulkInvoicesUrl + '?ids=' + this.selectedIds.join(',');
                        window.open(url, '_blank', 'noopener');
                    },
                    quickEmail: function() {
                        if (!this.selectedIds.length) {
                            toastr.warning('Please select at least one order.');
                            return;
                        }
                        var vm = this;
                        var count = this.selectedIds.length;
                        var msg = 'Send invoice email to <strong>' + count +
                            '</strong> selected order(s)? Each customer will receive their invoice at their registered email address.';
                        var doSend = function() {
                            vm.fullScreenLoadingText = 'Sending invoices...';
                            vm.fullScreenLoading = true;
                            var url = emailBulkInvoicesUrl + '?ids=' + vm.selectedIds.join(',');
                            fetch(url, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).then(function(r) {
                                return r.json();
                            }).then(function(res) {
                                vm.fullScreenLoading = false;
                                if (res.success) {
                                    toastr.success(res.message || 'Invoice(s) sent.');
                                } else {
                                    toastr.error(res.message || 'Failed to send.');
                                }
                                if (res.errors && res.errors.length) {
                                    res.errors.forEach(function(m) {
                                        toastr.warning(m);
                                    });
                                }
                            }).catch(function() {
                                vm.fullScreenLoading = false;
                                toastr.error('Network error. Please try again.');
                            });
                        };
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Email invoices?',
                                html: msg,
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, send',
                                cancelButtonText: 'Cancel'
                            }).then(function(result) {
                                if (result.isConfirmed) doSend();
                            });
                        } else {
                            if (!confirm('Send invoice email to ' + count + ' selected order(s)?'))
                                return;
                            doSend();
                        }
                    },
                    quickSms: function() {
                        toastr.info('SMS selected');
                    },
                    confirmDelete: function(row) {
                        var vm = this;
                        var doDelete = function() {
                            vm.loading = true;
                            fetch(deleteBase + '/' + row.slug, {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            }).then(function(r) {
                                return r.json().then(function(res) {
                                    return {
                                        ok: r.ok,
                                        res: res
                                    };
                                });
                            }).then(function(_ref) {
                                vm.loading = false;
                                if (_ref.ok) {
                                    toastr.success(_ref.res.success || 'Order deleted.');
                                    vm.fetchData(vm.pagination.current_page || 1);
                                } else {
                                    toastr.error(_ref.res.message || _ref.res.success ||
                                        'Failed to delete order.');
                                }
                            }).catch(function() {
                                vm.loading = false;
                                toastr.error('Network error. Please try again.');
                            });
                        };
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Delete this order?',
                                text: 'This action cannot be undone.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'Yes, delete it'
                            }).then(function(result) {
                                if (result.isConfirmed) doDelete();
                            });
                        } else {
                            if (!confirm('Delete this order?')) return;
                            doDelete();
                        }
                    },
                    goToPage(url) {
                        if (!url) return;
                        let parsedUrl = new URL(url);
                        let page = parsedUrl.searchParams.get('page');
                        if (page) {
                            this.fetchData(page);
                        }
                    }
                }
            });
        });
    </script>
@endsection
