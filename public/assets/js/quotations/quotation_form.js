/**
 * Quotation Form Vue App
 * Shared for both create and edit.
 */

function debounce(fn, wait) {
    var t;
    return function () {
        var args = arguments, ctx = this;
        clearTimeout(t);
        t = setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
}

window.onload = function () {

    var formEl = document.getElementById('quotationFormApp');
    if (!formEl) return;

    var existingData = {};
    try { existingData = JSON.parse(formEl.dataset.quotationData || '{}'); } catch (e) { }

    window.quotation_form_app = new Vue({
        el: '#quotationFormApp',

        data: function () {
            return {
                // Product search
                searchQuery: '',
                searchResults: [],
                loadingMore: false,

                // Warehouse selection (used to tag items)
                selectedWarehouse: existingData.product_warehouse_id || '',

                // Cart
                purchaseItems: [],

                // Charges
                otherChargesAmt: 0,
                discountOnAll: 0,
                discountOnAllType: 'in_percentage',
                discountToAllAmt: 0,
                roundOff: 0,

                // Payments
                paymentModes: {
                    cash: 0,
                    bkash: 0,
                    rocket: 0,
                    nogod: 0,
                    credit: 0,
                    cheque: 0,
                    bank: 0,
                },

                // Customer info (for display)
                selectedCustomerName: '',
                selectedCustomerPhone: '',
                customerDue: 0,

                isSubmitting: false,
            };
        },

        computed: {
            subtotal: function () {
                return this.purchaseItems.reduce(function (sum, item) {
                    if (!item.isVisible) return sum;
                    var price    = Number(item.price    || 0);
                    var qty      = Number(item.quantity || 0);
                    var discount = Number(item.discount || 0) / 100;
                    var tax      = Number(item.tax      || 0) / 100;
                    return sum + price * qty * (1 - discount) * (1 + tax);
                }, 0);
            },
            totalQuantity: function () {
                return this.purchaseItems.reduce(function (s, i) {
                    return s + (i.isVisible ? Number(i.quantity || 0) : 0);
                }, 0);
            },
            preGrandTotal: function () {
                return this.subtotal + this.otherChargesAmt - this.discountToAllAmt;
            },
            computedDecimalRoundOff: function () {
                var d = (this.preGrandTotal % 1).toFixed(2);
                return d === '0.00' ? 0 : Number(d);
            },
            grandTotal: function () {
                return Math.max(0, this.preGrandTotal - this.computedDecimalRoundOff - Number(this.roundOff));
            },
            totalPaid: function () {
                return Object.values(this.paymentModes).reduce(function (s, v) { return s + Number(v || 0); }, 0);
            },
            totalDue: function () {
                return this.grandTotal - this.totalPaid;
            },
        },

        mounted: function () {
            this._loadExistingData(existingData);
        },

        methods: {

            // ── Load existing data (edit mode) ────────────────────────────────

            _loadExistingData: function (d) {
                if (!d || !d.id) return;
                var vm = this;
                vm.discountOnAll     = d.discount_amount || 0;
                vm.discountOnAllType = d.discount_type   || 'in_percentage';
                vm.roundOff          = d.round_off_from_total || 0;

                // Re-hydrate payment modes from stored JSON
                var stored = d.payments || {};
                if (typeof stored === 'string') { try { stored = JSON.parse(stored); } catch (e) { stored = {}; } }
                Object.keys(stored).forEach(function (k) {
                    if (vm.paymentModes.hasOwnProperty(k)) vm.paymentModes[k] = stored[k];
                });

                // Re-hydrate cart items from order_products
                var products = d.order_products || [];
                products.forEach(function (p, idx) {
                    vm.purchaseItems.push({
                        id:                   idx,
                        product_id:           p.product_id,
                        name:                 p.product_name || (p.product ? p.product.name : ''),
                        price:                Number(p.product_price || 0),
                        quantity:             Number(p.qty || 1),
                        discount:             Number(p.discount_amount || 0),
                        tax:                  Number(p.tax || 0),
                        available_stock:      null,
                        has_variant:          false,
                        has_unit_price:       false,
                        variants:             [],
                        unit_prices:          [],
                        selected_variant_id:  p.variant_id    || null,
                        selected_unit_price_id: p.unit_price_id || null,
                        selected_variant:     null,
                        selected_unit_price:  null,
                        isVisible:            true,
                    });
                });

                vm.$nextTick(function () {
                    vm.calcOtherCharges();
                    vm.calcDiscountOnAll();
                });
            },

            // ── Product search ────────────────────────────────────────────────

            getData: function () {
                if (this.searchQuery.length > 1) {
                    this.fetchProducts();
                } else {
                    this.searchResults = [];
                }
            },

            fetchProducts: debounce(function () {
                var vm = this;
                vm.loadingMore = true;
                axios.post('/internal-api/search/products?query=' + encodeURIComponent(vm.searchQuery), {}).then(function (r) {
                    try { vm.searchResults = r.data.data.data; } catch (e) { vm.searchResults = []; }
                }).finally(function () { vm.loadingMore = false; });
            }, 500),

            // ── Cart manipulation ─────────────────────────────────────────────

            addRow: function (product) {
                var vm = this;
                var customerId = document.getElementById('customer_id') ? document.getElementById('customer_id').value : null;
                if (!customerId) {
                    if (typeof toastr !== 'undefined') toastr.error('Please select a customer first.');
                    return;
                }

                var hasVariant   = product.has_variant == 1 && product.variants && product.variants.length > 0;
                var hasUnitPrice = product.has_unit_price && product.unit_prices && product.unit_prices.length > 0;

                var price = product.price;
                var stock = product.stock;
                var selVariant    = null;
                var selUnitPrice  = null;

                if (hasVariant) {
                    selVariant = product.variants[0];
                    price = (selVariant.discount_price > 0 ? selVariant.discount_price : selVariant.price) || price;
                    stock = selVariant.stock;
                } else if (hasUnitPrice) {
                    selUnitPrice = product.unit_prices[0];
                    price = (selUnitPrice.discount_price > 0 ? selUnitPrice.discount_price : selUnitPrice.price) || price;
                } else {
                    price = (product.discount_price > 0 ? product.discount_price : product.price) || price;
                }

                // De-duplicate
                var existing = vm.purchaseItems.find(function (i) {
                    if (i.product_id !== product.id || !i.isVisible) return false;
                    if (hasVariant && selVariant) return i.selected_variant_id === selVariant.id;
                    if (hasUnitPrice && selUnitPrice) return i.selected_unit_price_id === selUnitPrice.id;
                    return !i.selected_variant_id && !i.selected_unit_price_id;
                });

                if (existing) {
                    existing.quantity = Number(existing.quantity) + 1;
                } else {
                    vm.purchaseItems.push({
                        id:                     vm.purchaseItems.length,
                        product_id:             product.id,
                        name:                   product.name,
                        price:                  price,
                        quantity:               1,
                        discount:               product.discount_parcent || 0,
                        tax:                    0,
                        available_stock:        stock,
                        has_variant:            hasVariant,
                        has_unit_price:         hasUnitPrice,
                        variants:               product.variants || [],
                        unit_prices:            product.unit_prices || [],
                        selected_variant_id:    selVariant    ? selVariant.id    : null,
                        selected_unit_price_id: selUnitPrice  ? selUnitPrice.id  : null,
                        selected_variant:       selVariant,
                        selected_unit_price:    selUnitPrice,
                        isVisible:              true,
                    });
                }

                vm.searchQuery   = '';
                vm.searchResults = [];
            },

            removeRow: function (index) {
                this.purchaseItems[index].isVisible = false;
                this.purchaseItems[index].quantity  = 0;
            },

            onVariantChange: function (item) {
                var v = item.variants.find(function (x) { return x.id === item.selected_variant_id; });
                if (v) {
                    item.selected_variant = v;
                    item.price            = v.discount_price > 0 ? v.discount_price : v.price;
                    item.available_stock  = v.stock;
                }
            },

            onUnitPriceChange: function (item) {
                var u = item.unit_prices.find(function (x) { return x.id === item.selected_unit_price_id; });
                if (u) {
                    item.selected_unit_price = u;
                    item.price               = u.discount_price > 0 ? u.discount_price : u.price;
                }
            },

            // ── Totals helpers ────────────────────────────────────────────────

            getItemTotal: function (item) {
                var price    = Number(item.price    || 0);
                var qty      = Number(item.quantity || 0);
                var discount = Number(item.discount || 0) / 100;
                var tax      = Number(item.tax      || 0) / 100;
                return price * qty * (1 - discount) * (1 + tax);
            },

            calcOtherCharges: function () {
                var subtotal     = this.subtotal;
                var percentTotal = 0;
                var fixedTotal   = 0;
                var amounts = document.querySelectorAll('.other_charges_amount');
                Array.prototype.forEach.call(amounts, function (el, idx) {
                    var typeEl = document.querySelector('.other_charges_type' + idx);
                    var val    = parseFloat(el.value) || 0;
                    if (typeEl && typeEl.value === 'percent') {
                        percentTotal += (subtotal * val) / 100;
                    } else {
                        fixedTotal += val;
                    }
                });
                this.otherChargesAmt = percentTotal + fixedTotal;
            },

            calcDiscountOnAll: function () {
                var preTotal = this.subtotal + this.otherChargesAmt;
                var amount   = Number(this.discountOnAll) || 0;
                if (this.discountOnAllType === 'in_percentage') {
                    this.discountToAllAmt = (preTotal * amount) / 100;
                } else {
                    this.discountToAllAmt = amount;
                }
            },

            setPaymentToDue: function (event, mode) {
                // On click, if field is 0 prefill with remaining due
                if (Number(event.target.value) === 0 && this.totalDue > 0) {
                    var alreadyAllocated = Object.keys(this.paymentModes).reduce(function (s, k) {
                        return k === mode ? s : s + Number(this.paymentModes[k] || 0);
                    }.bind(this), 0);
                    var remaining = this.grandTotal - alreadyAllocated;
                    if (remaining > 0) this.paymentModes[mode] = remaining.toFixed(2);
                }
                event.target.select();
            },

            // ── Form submit ───────────────────────────────────────────────────

            saveQuotation: function (event) {
                var vm = this;
                var form = event.target;

                // Validate cart has at least one item
                var visibleItems = vm.purchaseItems.filter(function (i) { return i.isVisible; });
                if (!visibleItems.length) {
                    if (typeof toastr !== 'undefined') toastr.error('Add at least one product.');
                    return;
                }

                var customerId = document.getElementById('customer_id') ? document.getElementById('customer_id').value : null;
                if (!customerId) {
                    if (typeof toastr !== 'undefined') toastr.error('Please select a customer.');
                    return;
                }

                vm.isSubmitting = true;
                var formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: formData,
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        vm.isSubmitting = false;
                        if (res.success) {
                            if (typeof toastr !== 'undefined') toastr.success(res.message || 'Saved.');
                            if (res.redirect) {
                                setTimeout(function () { window.location.href = res.redirect; }, 800);
                            }
                        } else {
                            if (res.errors) {
                                Object.values(res.errors).forEach(function (msgs) {
                                    msgs.forEach(function (m) {
                                        if (typeof toastr !== 'undefined') toastr.error(m);
                                    });
                                });
                            } else {
                                if (typeof toastr !== 'undefined') toastr.error(res.message || 'Save failed.');
                            }
                        }
                    })
                    .catch(function () {
                        vm.isSubmitting = false;
                        if (typeof toastr !== 'undefined') toastr.error('Network error. Please try again.');
                    });
            },
        },
    });
};
