/**
 * Quotation Form — Vue 2 App
 * Mirrors POS Desktop layout; reduced options for quotation workflow.
 * Loads pos-customer-manage + pos-product-item components (shared with POS).
 */
(function () {
    'use strict';

    function _debounce(fn, ms) {
        var t;
        return function () {
            var a = arguments, c = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(c, a); }, ms);
        };
    }

    var cfg = window.QUOTATION_CONFIG || {};
    var routes = cfg.routes || {};

    new Vue({
        el: '#quotation-app',

        data: function () {
            return {
                // ── Cart ─────────────────────────────────────────────────────
                cart: [],

                // ── Product search ────────────────────────────────────────────
                searchQuery: '',
                products: [],
                show_product_search_result: false,

                // ── Loading ───────────────────────────────────────────────────
                loading: { search: false, submit: false },

                // ── Warehouse ─────────────────────────────────────────────────
                selectedWarehouseId: null,
                warehouses: cfg.warehouses || [],

                // ── Customer (managed by pos-customer-manage component) ───────
                selectedCustomer: { id: 1, name: 'walking customer', phone: '', image: null },
                customerSources: [],

                // ── Order-level totals ────────────────────────────────────────
                totals: {
                    subtotal: 0,
                    discount: { type: 'fixed', value: 0, amount: 0 },
                    grand_total: 0,
                },
                extra_charge: 0,
                delivery_charge: 0,
                round_off: 0,

                // ── Payment methods ───────────────────────────────────────────
                paymentMethods: [],

                // ── Quotation meta ────────────────────────────────────────────
                order_status: 'pending',

                // ── UI state ──────────────────────────────────────────────────
                window_width: window.innerWidth,
                isEditMode: cfg.isEditMode || false,
                latestCode: '',
            };
        },

        computed: {
            subtotalComputed: function () {
                return this.cart.reduce(function (s, it) {
                    return s + (parseFloat(it.final_price) || 0);
                }, 0);
            },

            discountAmount: function () {
                var d = this.totals.discount;
                if (d.type === 'percent') {
                    return this.subtotalComputed * (parseFloat(d.value) || 0) / 100;
                }
                return Math.min(parseFloat(d.value) || 0, this.subtotalComputed);
            },

            grandTotal: function () {
                return Math.max(0,
                    this.subtotalComputed
                    - this.discountAmount
                    + (parseFloat(this.extra_charge) || 0)
                    + (parseFloat(this.delivery_charge) || 0)
                    - (parseFloat(this.round_off) || 0)
                );
            },

            paymentTotal: function () {
                return this.paymentMethods.reduce(function (s, pm) {
                    return s + (parseFloat(pm.amount) || 0);
                }, 0);
            },

            dueAmount: function () {
                return Math.max(0, this.grandTotal - this.paymentTotal);
            },
        },

        mounted: function () {
            var vm = this;

            vm._get_latest_code();

            if (vm.warehouses.length > 0) {
                vm.selectedWarehouseId = vm.warehouses[0]?.id;
            }

            vm._loadPaymentMethods();
            vm._loadCustomerSources();
            if (cfg.existingData && cfg.existingData.id) {
                vm._loadExistingData(cfg.existingData);
            }
            window.addEventListener('resize', function () {
                vm.window_width = window.innerWidth;
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest('#qt-search-wrap')) {
                    vm.show_product_search_result = false;
                }
            });
        },

        methods: {

            // ── Formatting ──────────────────────────────────────────────────
            formatMoney: function (n) {
                return (Number(n) || 0).toLocaleString('en-BD', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },

            randomHex: function (seed) {
                var s = String(seed), h = 0;
                for (var i = 0; i < s.length; i++) {
                    h = (h * 31 + s.charCodeAt(i)) & 0xffffff;
                }
                return ('000000' + h.toString(16)).slice(-6);
            },

            // ── Customer ────────────────────────────────────────────────────
            setSelectedCustomer: function (c) {
                this.selectedCustomer = c;
            },

            // ── Warehouse ───────────────────────────────────────────────────
            onWarehouseChange: function () {
                this.products = [];
                this.show_product_search_result = false;
            },

            // ── Product search ──────────────────────────────────────────────
            onSearchInput: _debounce(function () {
                var vm = this;
                var q = (vm.searchQuery || '').trim();
                if (!q) { vm.products = []; return; }
                vm.loading.search = true;
                vm.show_product_search_result = true;

                var url = routes.products
                    + '?q=' + encodeURIComponent(q)
                    + '&warehouse_id=' + (vm.selectedWarehouseId || '')
                    + '&page=1&per_page=20';

                fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        vm.products = (res.data && res.data.items) ? res.data.items : [];
                        vm.loading.search = false;

                    })
                    .catch(function () { vm.loading.search = false; });
            }, 350),

            selectProduct: function (p, extra) {
                var vm = this;
                extra = extra || {};
                var vKey = extra.variant_combination_key || null;
                var maxQty = extra.max_qty != null ? extra.max_qty : (p.stock || 0);
                // var unitPrice = p.unit_price || 0;
                var unitPrice = p.discount_price > 0 
                ? p.discount_price 
                : (p.unit_price || 0);
                var tempId = 'qt-' + Date.now() + '-' + (Math.random() * 1e6 | 0);

                if (vKey) {
                    var existing = vm.cart.find(function (it) {
                        return it.product_id === p.id && it.variant_combination_key === vKey;
                    });
                    if (existing) {
                        existing.qty = (parseFloat(existing.qty) || 0) + 1;
                        vm.recalcItem(existing);
                        vm.hide_product_search_result();
                        return;
                    }
                    vm.cart.push({
                        temp_id: tempId, product_id: p.id,
                        variant_id: null, unit_price_id: null,
                        variant_combination_key: vKey,
                        title: p.name + ' — ' + vKey, product_name: p.name,
                        image_url: p.image_url || '',
                        qty: 1, max_qty: maxQty, unit_price: unitPrice,
                        discount: { type: 'fixed', value: 0, percent: 0, fixed: 0 },
                        discount_price: unitPrice, final_price: unitPrice,
                    });
                    vm.hide_product_search_result();
                    return;
                }

                var simple = vm.cart.find(function (it) {
                    return it.product_id === p.id && !it.variant_combination_key;
                });
                if (simple) {
                    simple.qty = (parseFloat(simple.qty) || 0) + 1;
                    vm.recalcItem(simple);
                    vm.hide_product_search_result();
                    return;
                }
                vm.cart.push({
                    temp_id: tempId, product_id: p.id,
                    variant_id: null, unit_price_id: null,
                    variant_combination_key: null,
                    title: p.name, product_name: p.name,
                    image_url: p.image_url || '',
                    qty: 1, max_qty: maxQty, unit_price: unitPrice,
                    discount: { type: 'fixed', value: 0, percent: 0, fixed: 0 },
                    discount_price: unitPrice, final_price: unitPrice,
                });
                vm.hide_product_search_result();
            },

            hide_product_search_result: function () {
                this.show_product_search_result = false;
                // this.searchQuery = '';
                // this.products    = [];
            },

            removeItem: function (item, index) {
                this.cart.splice(index, 1);
            },

            // ── Cart value updates ───────────────────────────────────────────
            updateCartValue: function (event, key, item) {
                if (key === 'qty') item.qty = event.target.value;
                else if (key === 'unit_price') item.unit_price = event.target.value;
            },

            updateDiscountValue: function (event, type, item) {
                var val = parseFloat(event.target.value) || 0;
                if (type === 'percent') {
                    item.discount.percent = val;
                    item.discount.type = 'percent';
                    item.discount.value = val;
                } else {
                    item.discount.fixed = val;
                    item.discount.type = 'fixed';
                    item.discount.value = val;
                }
            },

            onItemDiscountChange: function (item) {
                this.recalcItem(item);
            },

            recalcItem: function (item) {
                var qty = parseFloat(item.qty) || 0;
                var up = parseFloat(item.unit_price) || 0;
                var gross = qty * up;
                var disc = 0;
                if (item.discount.type === 'percent') {
                    disc = gross * (parseFloat(item.discount.percent) || 0) / 100;
                } else {
                    disc = Math.min(parseFloat(item.discount.fixed) || 0, gross);
                }
                item.discount_price = qty > 0 ? Math.max(0, up - disc / qty) : up;
                item.final_price = Math.max(0, gross - disc);
            },

            recalcTotals: function () {
                this.totals.discount.amount = this.discountAmount;
                this.totals.grand_total = this.grandTotal;
            },

            // ── Increment / decrement (arrow keys) ───────────────────────────
            incrementValue: function (event, key, item) {
                var val = parseFloat(event.target.value) || 0;
                var newVal = val + 1;
                event.target.value = newVal;
                if (item) { this._applyItemKey(key, newVal, item); this.recalcItem(item); }
                else { this._applyGlobalKey(key, newVal); }
            },

            decrementValue: function (event, key, item) {
                var val = parseFloat(event.target.value) || 0;
                var newVal = Math.max(0, val - 1);
                event.target.value = newVal;
                if (item) { this._applyItemKey(key, newVal, item); this.recalcItem(item); }
                else { this._applyGlobalKey(key, newVal); }
            },

            updateValue: function (event, key) {
                this._applyGlobalKey(key, event.target.value);
            },

            _applyItemKey: function (key, val, item) {
                if (key === 'qty') item.qty = val;
                else if (key === 'unit_price') item.unit_price = val;
                else if (key === 'discount.percent') { item.discount.percent = val; item.discount.type = 'percent'; item.discount.value = val; }
                else if (key === 'discount.fixed') { item.discount.fixed = val; item.discount.type = 'fixed'; item.discount.value = val; }
            },

            _applyGlobalKey: function (key, val) {
                if (key === 'totals.discount.value') { this.totals.discount.value = val; this.recalcTotals(); }
                else if (key === 'extra_charge') { this.extra_charge = val; this.recalcTotals(); }
                else if (key === 'delivery_charge') { this.delivery_charge = val; this.recalcTotals(); }
                else if (key === 'round_off') { this.round_off = val; this.recalcTotals(); }
            },

            // ── Payment methods ──────────────────────────────────────────────
            onPaymentFocus: function (event, pm) {
                event.target.select();
                if (!parseFloat(pm.amount) && this.dueAmount > 0) {
                    var already = this.paymentMethods.reduce(function (s, p) {
                        return p.id === pm.id ? s : s + (parseFloat(p.amount) || 0);
                    }, 0);
                    var rem = this.grandTotal - already;
                    if (rem > 0) pm.amount = rem.toFixed(2);
                }
            },

            incrementPaymentValue: function (event, pm) {
                pm.amount = ((parseFloat(pm.amount) || 0) + 1).toFixed(2);
                event.target.value = pm.amount;
            },

            decrementPaymentValue: function (event, pm) {
                pm.amount = Math.max(0, (parseFloat(pm.amount) || 0) - 1).toFixed(2);
                event.target.value = pm.amount;
            },

            updatePaymentValue: function (event, pm) {
                pm.amount = event.target.value;
            },

            getPaymentMaxAmount: function (pm) {
                var others = this.paymentMethods.reduce(function (s, p) {
                    return p.id === pm.id ? s : s + (parseFloat(p.amount) || 0);
                }, 0);
                return Math.max(0, this.grandTotal - others).toFixed(2);
            },

            // ── Submit ───────────────────────────────────────────────────────
            submitQuotation: function () {
                var vm = this;

                if (!vm.selectedCustomer || !vm.selectedCustomer.id) {
                    toastr && toastr.error('Please select a customer.');
                    return;
                }
                if (!vm.selectedWarehouseId) {
                    toastr && toastr.error('Please select a warehouse.');
                    return;
                }
                if (!vm.cart.length) {
                    toastr && toastr.error('Add at least one product to the cart.');
                    return;
                }

                var saleDate = (document.getElementById('qt_sale_date') || {}).value || '';
                if (!saleDate) {
                    toastr && toastr.error('Sale date is required.');
                    return;
                }

                vm.loading.submit = true;
                var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute('content') || '';

                var payload = {
                    order_code: (document.getElementById('qt_order_code') || {}).value || '',
                    sale_date: saleDate,
                    due_date: (document.getElementById('qt_due_date') || {}).value || '',
                    shipping_date: (document.getElementById('qt_ship_date') || {}).value || '',
                    reference: (document.getElementById('qt_reference') || {}).value || '',
                    note: (document.getElementById('qt_note') || {}).value || '',
                    address: (document.getElementById('qt_address') || {}).value || '',
                    order_status: vm.order_status,
                    product_warehouse_id: vm.selectedWarehouseId,
                    customer_id: vm.selectedCustomer.id,
                    customer_name: vm.selectedCustomer.name,
                    customer_phone: vm.selectedCustomer.phone,
                    cart: vm.cart.map(function (it) {
                        return {
                            product_id: it.product_id,
                            variant_id: it.variant_id,
                            unit_price_id: it.unit_price_id,
                            variant_combination_key: it.variant_combination_key,
                            product_name: it.product_name || it.title,
                            qty: parseFloat(it.qty) || 1,
                            unit_price: parseFloat(it.unit_price) || 0,
                            discount_type: it.discount.type,
                            discount_value: parseFloat(it.discount.value) || 0,
                            final_price: parseFloat(it.final_price) || 0,
                        };
                    }),
                    totals: {
                        subtotal: vm.subtotalComputed,
                        discount: {
                            type: vm.totals.discount.type,
                            value: parseFloat(vm.totals.discount.value) || 0,
                            amount: vm.discountAmount,
                        },
                        extra_charge: parseFloat(vm.extra_charge) || 0,
                        delivery_charge: parseFloat(vm.delivery_charge) || 0,
                        round_off: parseFloat(vm.round_off) || 0,
                        grand_total: vm.grandTotal,
                    },
                    payments: vm.paymentMethods
                        .filter(function (p) { return parseFloat(p.amount) > 0; })
                        .map(function (p) {
                            return { method: p.title, method_id: p.id, amount: parseFloat(p.amount) };
                        }),
                    paid_amount: vm.paymentTotal,
                    due_amount: vm.dueAmount,
                };

                fetch(routes.saveQuotation, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(payload),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        vm.loading.submit = false;
                        if (res.success) {
                            toastr && toastr.success(res.message || 'Saved successfully.');
                            if (res.redirect) {
                                // setTimeout(function () { window.location.href = res.redirect; }, 700);
                                window.open(res.redirect, '_blank');
                            }
                            if (!vm.isEditMode) {
                                vm.cart = [];
                                vm.totals.discount.value = 0;
                                vm.totals.discount.amount = 0;
                                vm.extra_charge = 0;
                                vm.delivery_charge = 0;
                                vm.round_off = 0;
                                vm.recalcTotals();
                                vm._get_latest_code();
                            }
                        } else {
                            if (res.errors) {
                                Object.values(res.errors).forEach(function (msgs) {
                                    msgs.forEach(function (m) { toastr && toastr.error(m); });
                                });
                            } else {
                                toastr && toastr.error(res.message || 'Failed to save quotation.');
                            }
                        }
                    })
                    .catch(function () {
                        vm.loading.submit = false;
                        toastr && toastr.error('Network error. Please try again.');
                    });
            },

            _get_latest_code: function () {
                var vm = this;
                axios.get(routes.latestCode, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                })
                    .then(function (r) {
                        if (r.data && r.data.success) {
                            vm.latestCode = r.data.data || '';
                        }
                    });
            },

            // ── Load existing data (edit mode) ───────────────────────────────
            _loadExistingData: function (d) {
                var vm = this;
                if (!d || !d.id) return;

                vm.selectedWarehouseId = d.product_warehouse_id;
                vm.order_status = d.order_status || 'pending';

                vm.totals.discount.type = d.discount_type || 'fixed';
                vm.totals.discount.value = d.discount_amount || 0;

                var oc = d.other_charges;
                if (typeof oc === 'string') { try { oc = JSON.parse(oc); } catch (e) { oc = {}; } }
                vm.extra_charge = oc && oc.extra_charge ? parseFloat(oc.extra_charge) : 0;
                vm.delivery_charge = oc && oc.delivery_charge ? parseFloat(oc.delivery_charge) : 0;

                if (d.customer_id) {
                    vm.selectedCustomer = {
                        id: d.customer_id,
                        name: d.customer_name || (d.customer ? d.customer.name : ''),
                        phone: d.customer_phone || (d.customer ? d.customer.phone : ''),
                        address: d.address || (d.customer ? d.customer.address : ''),
                        available_advance: d.customer ? (parseFloat(d.customer.available_advance) || 0) : 0,
                        due_amount: d.customer ? (parseFloat(d.customer.due) || 0) : 0,
                        order_count: d.customer ? (d.customer.order_count || 0) : 0,
                    };
                }

                var prods = d.order_products || [];
                prods.forEach(function (p) {
                    var qty = parseFloat(p.qty) || 1;
                    var up = parseFloat(p.sale_price || p.product_price || 0);
                    var discVal = parseFloat(p.discount_amount || 0);
                    var discType = p.discount_type || 'fixed';
                    var finalPrc = parseFloat(p.total_price || (up * qty));
                    var discPc = discType === 'percent' ? discVal : 0;
                    var discFx = discType === 'fixed' ? discVal : 0;

                    vm.cart.push({
                        temp_id: 'edit-' + p.id,
                        product_id: p.product_id,
                        variant_id: p.variant_id || null,
                        unit_price_id: p.unit_price_id || null,
                        variant_combination_key: null,
                        title: p.product_name || '',
                        product_name: p.product_name || '',
                        image_url: cfg.image_url + '/' + (p.product_image || (p.product ? p.product.image : '') || ''),
                        qty: qty,
                        max_qty: 9999,
                        unit_price: up,
                        discount: { type: discType, value: discVal, percent: discPc, fixed: discFx },
                        discount_price: Math.max(0, up - (discType === 'fixed' ? (qty > 0 ? discVal / qty : 0) : up * discPc / 100)),
                        final_price: finalPrc,
                    });
                });

                vm.$nextTick(function () {
                    var raw = d.payments;
                    if (typeof raw === 'string') { try { raw = JSON.parse(raw); } catch (e) { raw = {}; } }
                    if (raw && typeof raw === 'object') {
                        vm.paymentMethods.forEach(function (pm) {
                            var k = pm.title.toLowerCase();
                            if (raw[k] !== undefined) pm.amount = raw[k];
                        });
                    }
                });
            },

            _loadPaymentMethods: function () {
                var vm = this;
                if (!routes.paymentMethods) return;
                fetch(routes.paymentMethods, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        vm.paymentMethods = (res.data || []).map(function (m) {
                            return { id: m.id, title: m.title, amount: 0 };
                        });
                        // After payment methods are loaded, re-apply edit-mode payments
                        if (cfg.existingData && cfg.existingData.id) {
                            var raw = cfg.existingData.payments;
                            if (typeof raw === 'string') { try { raw = JSON.parse(raw); } catch (e) { raw = {}; } }
                            if (raw && typeof raw === 'object') {
                                vm.paymentMethods.forEach(function (pm) {
                                    var k = pm.title.toLowerCase();
                                    if (raw[k] !== undefined) pm.amount = raw[k];
                                });
                            }
                        }
                    })
                    .catch(function () { });
            },

            _loadCustomerSources: function () {
                var vm = this;
                if (!routes.customerSource) return;
                fetch(routes.customerSource, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) { vm.customerSources = res.data || []; })
                    .catch(function () { });
            },
        },
    });

}());
