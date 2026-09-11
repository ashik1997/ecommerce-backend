@extends('backend.master')

@section('header_css')
    <link rel="stylesheet" href="/assets/plugins/select2/select2.min.css">
    <style>
        /* ── quotation-specific overrides ── */
        .qt-meta-panel .form-group { margin-bottom: 6px; }
        .qt-meta-panel label       { font-size: 12px; font-weight: 600; margin-bottom: 2px; }
        .qt-meta-panel input,
        .qt-meta-panel select,
        .qt-meta-panel textarea    { font-size: 13px; }
        .qt-status-row             { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 8px 0; }
        .qt-status-row label       { display: flex; align-items: center; gap: 5px; margin: 0; font-weight: 500; cursor: pointer; }
    </style>
@endsection

@section('header_js')
    <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
    <script>
        window.QUOTATION_CONFIG = {
            routes: {
                products:       "{{ route('pos.desktop.products') }}",
                customerSource: "{{ route('pos.desktop.customer-source') }}",
                paymentMethods: "{{ route('pos.get-payment-methods') }}",
                latestCode:     "{{ route('QuotationLatestCode') }}",
                saveQuotation:  "{{ isset($data) ? url('/quotation/update/' . $data->slug) : route('StoreProductOrderQuotation') }}",
            },
            warehouses:   @json($productWarehouses ?? []),
            image_url:    "{{ env('IMAGE_URL') }}",
            isEditMode:   {{ isset($data) ? 'true' : 'false' }},
            existingData: @json(isset($data) ? $data->load('order_products.product') : null),
        };

        /* pos-customer-manage component reads window.POS_DESKTOP_CONFIG.image_url */
        window.POS_DESKTOP_CONFIG = { image_url: window.QUOTATION_CONFIG.image_url };

        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add('lg_hide_menu');
        });
    </script>
    <script src="/assets/plugins/select2/select2.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ versioned_asset('assets/js/pos/pos_product_item.js') }}?v={{ time() }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/pos_customer_manage.js') }}?v={{ time() }}" defer></script>
    <script src="{{ versioned_asset('assets/js/quotations/quotation_vue.js') }}?v={{ time() }}" defer></script>
@endsection

@section('page_title')
    {{ isset($data) ? 'Edit Quotation' : 'New Quotation' }}
@endsection

@section('page_heading')
    {{ isset($data) ? 'Edit Quotation' : 'New Quotation' }}
@endsection

@section('content')
<div id="quotation-app" class="pos_page">

    {{-- Top header bar (mirrors POS header) --}}
    <header id="page-topbar" class="pos_header">
        <div class="navbar-header">
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm mr-2 d-lg-none header-item" id="vertical-menu-btn">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
                <button type="button" class="btn btn-sm mr-2 d-none d-lg-block header-item"
                    onclick="$('body').toggleClass('lg_hide_menu')" id="lg_menu_toggler">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
                <span class="ml-2 font-weight-bold" style="font-size: 15px; color: #444;">
                    <i class="feather-file-text mr-1"></i>
                    {{ isset($data) ? 'Edit Quotation: ' . $data->order_code : 'New Quotation' }}
                </span>
            </div>
            <div class="d-flex align-items-center">
                <a href="{{ route('ViewAllProductOrderQuotations') }}" class="btn btn-sm btn-secondary">
                    <i class="feather-arrow-left mr-1"></i> All Quotations
                </a>
            </div>
        </div>
    </header>

    <div class="pos-page" v-cloak>

        {{-- Warehouse + Customer row --}}
        <div class="pos_header">
            <div class="pos-warehouse-row">
                <div><label for="qt-warehouse">Warehouse</label></div>
                <div>
                    <select id="qt-warehouse" v-model="selectedWarehouseId" @change="onWarehouseChange">
                        {{-- <option :value="null">All Warehouses</option> --}}
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">
                            @{{ w.title }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="pos-customer-manage-row">
                <pos-customer-manage
                    :set-selected-customer="setSelectedCustomer"
                    :customer_sources="customerSources"
                    :selected-customer="selectedCustomer"
                ></pos-customer-manage>
            </div>
        </div>

        <div class="pos-top">

            {{-- ── LEFT: product search + cart ─────────────────────────── --}}
            <div class="pos-left">
                {{-- Search bar (no barcode for quotation) --}}
                <div class="pos-search-row" id="qt-search-wrap">
                    <div style="flex: 1;">
                        <input type="text" v-model="searchQuery"
                            @focus="show_product_search_result = true"
                            @input="onSearchInput"
                            :class="{ 'pos-search-loading': loading.search }"
                            class="w-100"
                            placeholder="Search product by name" ref="searchInput">
                    </div>
                </div>

                {{-- Cart table --}}
                <div class="cart_list_wrapper">
                    <table class="pos-cart-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="cart_col_width">Qty</th>
                                <th class="cart_col_width">Unit Price</th>
                                <th class="cart_col_width">Disc (%)</th>
                                <th class="cart_col_width">Disc (Tk)</th>
                                <th class="cart_col_width">Disc Price</th>
                                <th class="cart_col_width">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(it, index) in cart" :key="it.temp_id">
                                {{-- Mobile: item name row --}}
                                <tr v-if="window_width < 1300">
                                    <td colspan="6">
                                        <div class="pos-cart-item-cell">
                                            <img :src="it.image_url" class="pos-cart-thumb" alt="">
                                            <div class="pos-cart-item-title pos-cart-item-title-ellipsis" style="flex: 1;" :title="it.title">
                                                <div style="display: flex; justify-content: space-between;">
                                                    <div>@{{ it.title }}</div>
                                                    <div>
                                                        <i class="feather-trash-2" style="cursor: pointer; color: red; font-size: 12px;" @click="removeItem(it, index)"></i>
                                                    </div>
                                                </div>
                                                <div class="pos-cart-item-variant" v-if="it.variant_combination_key">
                                                    <span>@{{ it.variant_combination_key }}</span>
                                                    <span>(avl: @{{ it.max_qty }})</span>
                                                    <span v-if="it.unit_code" :style="'border: 1px solid #' + randomHex(it.unit_code || it.temp_id) + '; color: black; padding: 2px 4px; border-radius: 4px;'">@{{ it.unit_code }}</span>
                                                </div>
                                                <div class="pos-cart-item-variant" v-if="!it.variant_combination_key">
                                                    <span>Avl: @{{ it.max_qty }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Desktop: prices + qty row --}}
                                <tr>
                                    <td v-if="window_width >= 1300">
                                        <div class="pos-cart-item-cell">
                                            <img :src="it.image_url" class="pos-cart-thumb" alt="">
                                            <div class="pos-cart-item-title pos-cart-item-title-ellipsis" style="flex: 1;" :title="it.title">
                                                <div class="pos-cart-item-title-row">
                                                    <div class="pos-cart-item-title-text">@{{ it.title }}</div>
                                                    <div class="pos-cart-item-title-remove">
                                                        <i class="feather-trash-2" style="cursor: pointer; color: red; font-size: 12px;" @click="removeItem(it, index)"></i>
                                                    </div>
                                                </div>
                                                <div class="pos-cart-item-variant" v-if="it.variant_combination_key">
                                                    <span>@{{ it.variant_combination_key }}</span>
                                                    <span>(avl: @{{ it.max_qty }})</span>
                                                    <span v-if="it.unit_code" :style="'border: 1px solid #' + randomHex(it.unit_code || it.temp_id) + '; color: black; padding: 2px 4px; border-radius: 4px;'">@{{ it.unit_code }}</span>
                                                </div>
                                                <div class="pos-cart-item-variant" v-if="!it.variant_combination_key">
                                                    <span>Avl: @{{ it.max_qty }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart_col_width">
                                        <input type="text" class="pos-cart-input" :value="it.qty"
                                            @focus="$event.target.select()"
                                            @keyup.up.prevent="incrementValue($event, 'qty', it)"
                                            @keyup.down.prevent="decrementValue($event, 'qty', it)"
                                            @input="updateCartValue($event, 'qty', it)"
                                            @blur="recalcItem(it)">
                                    </td>
                                    <td class="cart_col_width">
                                        
                                        <input type="text" class="pos-cart-input" :value="it.unit_price"
                                            @focus="$event.target.select()"
                                            @keyup.up.prevent="incrementValue($event, 'unit_price', it)"
                                            @keyup.down.prevent="decrementValue($event, 'unit_price', it)"
                                            @input="updateCartValue($event, 'unit_price', it)"
                                            @blur="recalcItem(it)">
                                    </td>
                                    <td class="cart_col_width">
                                        <input type="text" class="pos-cart-input" :value="it.discount.percent"
                                            @focus="$event.target.select()"
                                            @keyup.up.prevent="incrementValue($event, 'discount.percent', it)"
                                            @keyup.down.prevent="decrementValue($event, 'discount.percent', it)"
                                            @input="updateDiscountValue($event, 'percent', it)"
                                            @blur="onItemDiscountChange(it)">
                                    </td>
                                    <td class="cart_col_width">
                                        <input type="text" class="pos-cart-input" :value="it.discount.fixed"
                                            @focus="$event.target.select()"
                                            @keyup.up.prevent="incrementValue($event, 'discount.fixed', it)"
                                            @keyup.down.prevent="decrementValue($event, 'discount.fixed', it)"
                                            @input="updateDiscountValue($event, 'fixed', it)"
                                            @blur="onItemDiscountChange(it)">
                                    </td>
                                    <td class="cart_col_width">
                                        <input type="text" disabled class="pos-cart-input" :value="it.discount_price">
                                    </td>
                                    <td class="cart_col_width">@{{ formatMoney(it.final_price) }}</td>
                                </tr>
                            </template>
                            <tr v-if="cart.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">
                                    <i class="feather-shopping-cart mr-1"></i> Cart is empty — search and add products above
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Product search results (same as POS) --}}
                <div class="product_list_wrapper product_list_wrapper_v2" v-if="show_product_search_result">
                    <div class="pos-loading-overlay" v-if="loading.search">
                        <div class="pos-loading-container">
                            <div class="pos-loading-spinner"></div>
                            <div class="pos-loading-text">Loading products...</div>
                        </div>
                    </div>
                    <div class="pos-products-grid pos-products-grid-v2">
                        <pos-product-item
                            v-for="p in products" :key="p.id" :p="p"
                            :formatMoney="formatMoney"
                            :product-item-select="selectProduct"
                            :hide_product_search_result="hide_product_search_result"
                            :order_type="`quotation`"
                        ></pos-product-item>
                        <template v-if="loading.search && products.length === 0">
                            <div class="pos-product-skeleton" v-for="n in 5" :key="'sk-' + n"></div>
                        </template>
                    </div>
                    <div class="pos-product-search-result-footer text-center mt-2">
                        <button type="button" class="btn btn-sm btn-danger" @click="show_product_search_result = false">Close</button>
                    </div>
                </div>
            </div>{{-- end .pos-left --}}

            {{-- ── RIGHT: meta + totals + payments + actions ──────────────── --}}
            <div class="pos-right pos_right_v2" style="display: flex; flex-direction: column; gap: 10px; justify-content: space-between;">

                {{-- Quotation meta fields --}}
                <div class="qt-meta-panel border rounded p-2 bg-light">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Quotation Code <span class="text-danger">*</span></label>
                                <input type="text" v-model="latestCode" readonly id="qt_order_code" class="form-control form-control-sm"
                                    {{ isset($data) ? 'readonly' : '' }}>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Sale Date <span class="text-danger">*</span></label>
                                <input type="date" id="qt_sale_date" class="form-control form-control-sm"
                                    value="{{ isset($data) ? \Carbon\Carbon::parse($data->sale_date)->format('Y-m-d') : date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" id="qt_due_date" class="form-control form-control-sm"
                                    value="{{ isset($data) && $data->due_date ? \Carbon\Carbon::parse($data->due_date)->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Shipping Date</label>
                                <input type="date" id="qt_ship_date" class="form-control form-control-sm"
                                    value="{{ isset($data) && $data->shipping_date ? \Carbon\Carbon::parse($data->shipping_date)->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Reference</label>
                                <input type="text" id="qt_reference" class="form-control form-control-sm"
                                    value="{{ isset($data) ? $data->reference : '' }}"
                                    placeholder="Ref. #">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" id="qt_address" class="form-control form-control-sm"
                                    value="{{ isset($data) ? $data->address : '' }}"
                                    placeholder="Delivery address">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label>Note</label>
                                <textarea id="qt_note" class="form-control form-control-sm" rows="2"
                                    placeholder="Internal note / instruction">{{ isset($data) ? $data->note : '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Totals (same layout as POS) --}}
                <div>
                    <div class="pos-totals">
                        <div class="pos-totals-row pos-totals-subtotal">
                            <span>Subtotal</span>
                            <span>@{{ formatMoney(subtotalComputed) }}</span>
                        </div>

                        {{-- Order-level discount --}}
                        <div class="pos-totals-row">
                            <div>
                                <span>Discount</span>
                                <label class="mr-2" for="qt_disc_fixed">
                                    <input type="radio" name="qt_discount_type" value="fixed" id="qt_disc_fixed"
                                        v-model="totals.discount.type" @change="recalcTotals">
                                    <span>Fixed</span>
                                </label>
                                <label for="qt_disc_pct" class="mr-2">
                                    <input type="radio" name="qt_discount_type" value="percent" id="qt_disc_pct"
                                        v-model="totals.discount.type" @change="recalcTotals">
                                    <span>%</span>
                                </label>
                            </div>
                            <span>
                                <input type="text" class="pos-cart-input"
                                    :value="totals.discount.value"
                                    :placeholder="totals.discount.type === 'percent' ? '%' : '0'"
                                    @focus="$event.target.select()"
                                    @keyup.up.prevent="incrementValue($event, 'totals.discount.value', null)"
                                    @keyup.down.prevent="decrementValue($event, 'totals.discount.value', null)"
                                    @input="updateValue($event, 'totals.discount.value')">
                                <span class="text-muted ml-1">= @{{ formatMoney(discountAmount) }}</span>
                            </span>
                        </div>

                        <div class="pos-totals-row">
                            <span>Extra Charge</span>
                            <span>
                                <input type="text" class="pos-cart-input"
                                    :value="extra_charge"
                                    @focus="$event.target.select()"
                                    @keyup.up.prevent="incrementValue($event, 'extra_charge', null)"
                                    @keyup.down.prevent="decrementValue($event, 'extra_charge', null)"
                                    @input="updateValue($event, 'extra_charge')"
                                    @blur="recalcTotals">
                            </span>
                        </div>

                        <div class="pos-totals-row">
                            <span>Delivery Charge</span>
                            <span>
                                <input type="text" class="pos-cart-input"
                                    :value="delivery_charge"
                                    @focus="$event.target.select()"
                                    @keyup.up.prevent="incrementValue($event, 'delivery_charge', null)"
                                    @keyup.down.prevent="decrementValue($event, 'delivery_charge', null)"
                                    @input="updateValue($event, 'delivery_charge')"
                                    @blur="recalcTotals">
                            </span>
                        </div>

                        <div class="pos-totals-row">
                            <span>Round Off</span>
                            <span>
                                <input type="text" class="pos-cart-input"
                                    :value="round_off"
                                    @focus="$event.target.select()"
                                    @keyup.up.prevent="incrementValue($event, 'round_off', null)"
                                    @keyup.down.prevent="decrementValue($event, 'round_off', null)"
                                    @input="updateValue($event, 'round_off')"
                                    @blur="recalcTotals">
                            </span>
                        </div>

                        <div class="pos-totals-row pos-totals-grand pos_total_grand_total">
                            <span>Grand Total</span>
                            <span>@{{ formatMoney(grandTotal) }}</span>
                        </div>

                        <hr/>

                        {{-- Payment methods --}}
                        <div v-for="pm in paymentMethods" :key="pm.id" class="pos-totals-row">
                            <span><b>@{{ pm.title }}</b></span>
                            <span>
                                <input class="pos-cart-input" type="text"
                                    :value="pm.amount"
                                    :max="getPaymentMaxAmount(pm)"
                                    @focus="onPaymentFocus($event, pm)"
                                    @keyup.up.prevent="incrementPaymentValue($event, pm)"
                                    @keyup.down.prevent="decrementPaymentValue($event, pm)"
                                    @input="updatePaymentValue($event, pm)">
                            </span>
                        </div>

                        <div class="pos-totals-row pos-totals-grand">
                            <span><b>Paid Amount</b></span>
                            <span>@{{ formatMoney(paymentTotal) }}</span>
                        </div>
                        <div class="pos-totals-row pos-totals-grand">
                            <span><b>Due Amount</b></span>
                            <span>@{{ formatMoney(dueAmount) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions panel (mirrors POS .pos-actions) --}}
                <div class="pos-actions">
                    {{-- Quotation status (pending / in_review / invoiced / canceled) --}}
                    <div class="delivery_info_container">
                        <div class="qt-status-row">
                            <label class="font-weight-bold pr-2 mb-0">Status:</label>
                            <label>
                                <input type="radio" name="qt_order_status" value="pending"   v-model="order_status"> Pending
                            </label>
                            <label>
                                <input type="radio" name="qt_order_status" value="in_review" v-model="order_status"> In Review
                            </label>
                            <label>
                                <input type="radio" name="qt_order_status" value="invoiced"  v-model="order_status"> Invoiced
                            </label>
                            <label>
                                <input type="radio" name="qt_order_status" value="canceled"  v-model="order_status"> Canceled
                            </label>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success w-100 mt-2" @click="submitQuotation"
                        :disabled="loading.submit" :class="{ 'pos-btn-loading': loading.submit }">
                        <span v-if="!loading.submit">
                            @{{ isEditMode ? 'Update Quotation' : 'Save Quotation' }}
                        </span>
                        <span v-else>Saving...</span>
                    </button>
                </div>

            </div>{{-- end .pos-right --}}
        </div>{{-- end .pos-top --}}
    </div>{{-- end .pos-page --}}
</div>{{-- end #quotation-app --}}
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* Quotation form is desktop-only; redirect small screens */
        if (window.innerWidth < 768) {
            window.location.href = "{{ route('ViewAllProductOrderQuotations') }}";
        }
    });
</script>
@endpush
