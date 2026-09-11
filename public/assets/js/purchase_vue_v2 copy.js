/**
 * purchase_vue.js
 * Purchase Order — Vue 2 app
 * Handles: product search (same API as stock adjustment), variant selection,
 * barcode modal, row management, and all totals computation.
 */

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

window.onload = function () {
    new Vue({
        el: '#formApp',
        data() {
            return {
                /* ── search ── */
                searchQuery: '',
                searchResults: [],
                loadingMore: false,
                dropdownVisible: false,

                /* ── pending product (before add to table) ── */
                pendingProduct: null,
                pendingVariantSelections: {},
                pendingMatchedVariant: null,

                /* ── warehouse / rooms ── */
                selectedWarehouse: '',
                rooms: [],
                cartoonsByRoom: {},

                /* ── purchase rows ── */
                purchaseItems: [],

                /* ── totals ── */
                other_charges_amt: 0,

                /* ── form submit (only on button click) ── */
                formSubmitting: false,
                formErrors: [],

                /* ── barcode modal ── */
                barcodeModal: {
                    open: false,
                    rowIndex: null,
                    item: null,
                    product: null,
                    qty: 0,
                    barcodes: [],
                    scanEntry: '',
                    commonBarcode: '',
                    editingVariant: false,
                    variantSelections: {},
                    matchedVariant: null,
                },
            };
        },

        computed: {
            totalProducts() {
                return this.purchaseItems.length;
            },
            totalQuantity() {
                return this.purchaseItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
            },
            subtotal() {
                return this.purchaseItems.reduce((sum, item) => sum + this.getItemTotalPrice(item), 0);
            },
            grand_total_amt() {
                return Math.max(0, this.subtotal + this.other_charges_amt);
            },
        },

        methods: {
            /* ── Form submit: only on button click; Enter does nothing ── */
            onFormSubmit(e) {
                e.preventDefault();
                e.stopPropagation();
            },
            async submitPurchaseForm() {
                if (this.formSubmitting) return;
                var form = document.getElementById('purchaseForm');
                if (!form) return;

                this.formErrors = [];
                this.formSubmitting = true;

                try {
                    var url = form.getAttribute('action');
                    var formData = new FormData(form);

                    var response = await axios.post(url, formData, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    var data = response.data;
                    if (data && typeof data === 'object' && data.success) {
                        if (typeof toastr !== 'undefined' && toastr && typeof toastr.success === 'function') {
                            toastr.success(data.message || 'Purchase submitted successfully!');
                        }
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        this.formSubmitting = false;
                    }
                } catch (error) {
                    this.formSubmitting = false;

                    if (error.response && error.response.status === 422 && error.response.data && error.response.data.errors) {
                        var errors = error.response.data.errors;
                        this.formErrors = Object.keys(errors).reduce(function (acc, key) {
                            return acc.concat(errors[key]);
                        }, []);
                    } else if (error.response && error.response.data && error.response.data.message) {
                        this.formErrors = [error.response.data.message];
                    } else {
                        this.formErrors = ['An unexpected error occurred while submitting the purchase.'];
                    }

                    if (typeof toastr !== 'undefined' && toastr && typeof toastr.error === 'function') {
                        toastr.error(this.formErrors[0] || 'Error submitting purchase');
                    }
                }
            },

            /* ══════════════════════════════════════════
               PRODUCT SEARCH
            ══════════════════════════════════════════ */
            getData() {
                if ((this.searchQuery || '').trim().length > 1) {
                    this.fetchProducts();
                    this.dropdownVisible = true;
                } else {
                    this.searchResults = [];
                    this.dropdownVisible = false;
                }
            },

            fetchProducts: debounce(function () {
                this.loadingMore = true;
                axios.get(`/stock-adjustment/search-products`, {
                    params: { q: this.searchQuery, page: 1 }
                })
                    .then(res => {
                        try {
                            this.searchResults = res.data.data.items || [];
                        } catch (e) {
                            this.searchResults = [];
                        }
                        this.dropdownVisible = true;
                    })
                    .catch(err => console.error('Product search error:', err))
                    .finally(() => { this.loadingMore = false; });
            }, 350),

            /* ══════════════════════════════════════════
               PENDING PRODUCT / VARIANT SELECTION
            ══════════════════════════════════════════ */
            selectProduct(product) {
                this.pendingProduct = product;
                this.pendingVariantSelections = {};
                this.pendingMatchedVariant = null;
                this.searchQuery = '';
                this.searchResults = [];
                this.dropdownVisible = false;
            },

            getPendingVariantAttributes() {
                if (!this.pendingProduct || !this.pendingProduct.product_variants) return [];
                return Object.keys(this.pendingProduct.product_variants);
            },

            onPendingVariantChange() {
                this.pendingMatchedVariant = this.findMatchedVariant(
                    this.pendingProduct,
                    this.pendingVariantSelections
                );
            },

            getPendingCombinationLabel() {
                const attrs = this.getPendingVariantAttributes();
                return attrs.filter(a => this.pendingVariantSelections[a]).length > 0;
            },

            /* ══════════════════════════════════════════
               ADD PRODUCT TO TABLE
            ══════════════════════════════════════════ */
            addProductToTable() {
                const product = this.pendingProduct;
                if (!product) return;

                if (product.has_variants) {
                    if (!this.pendingMatchedVariant) {
                        if (typeof toastr !== 'undefined') toastr.warning('Please select a valid variant combination first.');
                        return;
                    }
                    const row = this.createRow(product, this.pendingMatchedVariant, this.pendingVariantSelections);
                    this.purchaseItems.push(row);
                } else {
                    // Prevent duplicate non-variant products
                    const exists = this.purchaseItems.find(i => i.product_id === product.id && !i.variant_combination_id);
                    if (exists) {
                        if (typeof toastr !== 'undefined') toastr.info('Product already added.');
                        return;
                    }
                    const row = this.createRow(product, null, {});
                    this.purchaseItems.push(row);
                }

                // Reset pending
                this.pendingProduct = null;
                this.pendingVariantSelections = {};
                this.pendingMatchedVariant = null;
            },

            createRow(product, matchedVariant, selections) {
                const isVariant = !!matchedVariant;
                const variantLabel = isVariant
                    ? Object.entries(matchedVariant)
                        .filter(([k]) => k !== 'id')
                        .map(([k, v]) => `${k}: ${v}`)
                        .join(' | ')
                    : '';

                const displayName = isVariant
                    ? `${product.name} (${variantLabel})`
                    : product.name;

                return {
                    rowKey: `${product.id}-${isVariant ? 'v' + matchedVariant.id : 'p'}-${Date.now()}`,
                    product_id: product.id,
                    id: product.id,
                    name: product.name,
                    display_name: displayName,
                    variantLabel: variantLabel,
                    variant_combination_id: isVariant ? matchedVariant.id : null,
                    variantData: isVariant ? { ...selections } : null,
                    productRef: product,
                    price: Number(product.unit_price || product.main_price || product.price || 0),
                    quantity: 0,
                    discount: 0,
                    tax: 0,
                    previous_stock: Number(product.stock || 0),
                    warehouse_room_id: '',
                    warehouse_cartoon_id: '',
                    cartoonOptions: [],
                    barcodes: [],
                };
            },

            removeRow(index) {
                this.purchaseItems.splice(index, 1);
            },

            /* ══════════════════════════════════════════
               VARIANT MATCHING UTILITY
            ══════════════════════════════════════════ */
            findMatchedVariant(product, selections) {
                if (!product || !product.product_variant_combinations) return null;
                const selectedAttrs = Object.keys(selections || {}).filter(k => selections[k]);
                if (!selectedAttrs.length) return null;

                return product.product_variant_combinations.find(comb =>
                    selectedAttrs.every(attr => comb[attr] === selections[attr])
                ) || null;
            },

            /* ══════════════════════════════════════════
               NUMERIC INPUT HANDLING
            ══════════════════════════════════════════ */
            handleItemNumericInput(item, field, label) {
                const raw = item[field];
                if (raw === '' || raw === null || raw === undefined) {
                    this.$set(item, field, '');
                    if (field === 'quantity') this.$set(item, 'barcodes', []);
                    return;
                }
                const numeric = Number(raw);
                if (Number.isNaN(numeric)) {
                    if (typeof toastr !== 'undefined') toastr.error(`${label} must be a number`);
                    this.$set(item, field, 0);
                } else {
                    this.$set(item, field, numeric);
                    if (field === 'quantity') {
                        const qty = Math.max(0, parseInt(numeric) || 0);
                        this.syncBarcodesLength(item, qty);
                    }
                }
            },

            syncBarcodesLength(item, qty) {
                const current = item.barcodes || [];
                if (current.length < qty) {
                    while (item.barcodes.length < qty) item.barcodes.push('');
                } else if (current.length > qty) {
                    item.barcodes.splice(qty);
                }
            },

            /* ══════════════════════════════════════════
               ROW TOTAL
            ══════════════════════════════════════════ */
            getItemTotalPrice(item) {
                const qty   = Number(item.quantity || 0);
                const price = Number(item.price || 0);
                let total   = qty * price;
                if (item.discount > 0) total -= (Number(item.discount) / 100) * total;
                if (item.tax > 0)      total += (Number(item.tax) / 100) * total;
                return total;
            },

            /* ══════════════════════════════════════════
               WAREHOUSE / ROOMS / CARTOONS
            ══════════════════════════════════════════ */
            getRooms() {
                if (!this.selectedWarehouse) {
                    this.rooms = [];
                    this.cartoonsByRoom = {};
                    this.purchaseItems.forEach(item => {
                        item.warehouse_room_id = '';
                        item.warehouse_cartoon_id = '';
                        item.cartoonOptions = [];
                    });
                    return;
                }
                axios.get(`/api/get-rooms/${this.selectedWarehouse}`)
                    .then(res => { this.rooms = res.data || []; })
                    .catch(err => console.error('Rooms error:', err));
            },

            loadCartoonsForRoom(roomId, silent = false) {
                if (!roomId || !this.selectedWarehouse) return Promise.resolve([]);
                if (this.cartoonsByRoom[roomId]) return Promise.resolve(this.cartoonsByRoom[roomId]);
                return axios.get(`/api/get-cartoons/${this.selectedWarehouse}/${roomId}`)
                    .then(res => {
                        const cartoons = res.data || [];
                        this.$set(this.cartoonsByRoom, roomId, cartoons);
                        return cartoons;
                    })
                    .catch(err => { if (!silent) console.error('Cartoons error:', err); return []; });
            },

            onRowRoomChange(item) {
                if (!item.warehouse_room_id) {
                    item.warehouse_cartoon_id = '';
                    item.cartoonOptions = [];
                    return;
                }
                this.loadCartoonsForRoom(item.warehouse_room_id).then(cartoons => {
                    item.cartoonOptions = cartoons;
                    if (!cartoons.find(c => Number(c.id) === Number(item.warehouse_cartoon_id))) {
                        item.warehouse_cartoon_id = '';
                    }
                });
            },

            /* ══════════════════════════════════════════
               OTHER CHARGES (legacy DOM-based)
            ══════════════════════════════════════════ */
            calc_other_charges() {
                const subtotal = this.subtotal;
                let percentTotal = 0, fixedTotal = 0;
                const amounts = [...document.querySelectorAll('.other_charges_amount')];
                amounts.forEach((el, idx) => {
                    const type = document.querySelector('.other_charges_type' + idx);
                    const value = Number(el.value);
                    if (Number.isNaN(value) || !value) return;
                    if (type && type.value === 'percent') percentTotal += (subtotal * value) / 100;
                    else fixedTotal += value;
                });
                this.other_charges_amt = percentTotal + fixedTotal;
            },

            /* ══════════════════════════════════════════
               BARCODE MODAL
            ══════════════════════════════════════════ */
            openBarcodeModal(index) {
                const item = this.purchaseItems[index];
                if (!item) return;

                const qty = Math.max(0, parseInt(item.quantity) || 0);
                const barcodes = [...(item.barcodes || [])];
                // Ensure length matches qty
                while (barcodes.length < qty) barcodes.push('');
                if (barcodes.length > qty) barcodes.splice(qty);

                this.barcodeModal = {
                    open: true,
                    rowIndex: index,
                    item: item,
                    product: item.productRef || null,
                    qty: qty,
                    barcodes: barcodes,
                    scanEntry: '',
                    commonBarcode: '',
                    editingVariant: false,
                    variantSelections: item.variantData ? { ...item.variantData } : {},
                    matchedVariant: null,
                };

                this.$nextTick(() => {
                    if (this.$refs.modalBarcodeInput) this.$refs.modalBarcodeInput.focus();
                });
            },

            closeBarcodeModal() {
                this.barcodeModal.open = false;
            },

            saveBarcodeModal() {
                const index = this.barcodeModal.rowIndex;
                if (index === null || index === undefined) return;

                const item = this.purchaseItems[index];
                const qty  = this.barcodeModal.qty;
                const barcodes = [...this.barcodeModal.barcodes];

                this.$set(item, 'quantity', qty);
                this.$set(item, 'barcodes', barcodes);

                this.closeBarcodeModal();
            },

            clearModalBarcodes() {
                this.barcodeModal.qty = 0;
                this.barcodeModal.barcodes = [];
                this.barcodeModal.scanEntry = '';
                this.barcodeModal.commonBarcode = '';
            },

            addModalBarcode() {
                const trimmed = (this.barcodeModal.scanEntry || '').toString().trim();
                if (!trimmed) return;
                this.barcodeModal.barcodes.push(trimmed);
                this.barcodeModal.qty = this.barcodeModal.barcodes.length;
                this.barcodeModal.scanEntry = '';
            },

            onModalQtyChange() {
                let qty = parseInt(this.barcodeModal.qty) || 0;
                if (qty < 0) qty = 0;
                this.barcodeModal.qty = qty;

                if (qty === 0) { this.barcodeModal.barcodes = []; return; }
                while (this.barcodeModal.barcodes.length < qty) this.barcodeModal.barcodes.push('');
                if (this.barcodeModal.barcodes.length > qty) this.barcodeModal.barcodes.splice(qty);
            },

            removeModalBarcode(index) {
                this.barcodeModal.barcodes.splice(index, 1);
                this.barcodeModal.qty = this.barcodeModal.barcodes.length;
            },

            applyCommonModalBarcode() {
                const code = (this.barcodeModal.commonBarcode || '').toString().trim();
                if (!code || !this.barcodeModal.barcodes.length) return;
                this.barcodeModal.barcodes = this.barcodeModal.barcodes.map(() => code);
            },

            /* ── Modal variant change ── */
            toggleModalVariantEdit() {
                this.barcodeModal.editingVariant = !this.barcodeModal.editingVariant;
                if (this.barcodeModal.editingVariant) {
                    this.barcodeModal.variantSelections = this.barcodeModal.item.variantData
                        ? { ...this.barcodeModal.item.variantData } : {};
                    this.barcodeModal.matchedVariant = null;
                }
            },

            getModalVariantAttributes() {
                const p = this.barcodeModal.product;
                if (!p || !p.product_variants) return [];
                return Object.keys(p.product_variants);
            },

            onModalVariantChange() {
                this.barcodeModal.matchedVariant = this.findMatchedVariant(
                    this.barcodeModal.product,
                    this.barcodeModal.variantSelections
                );
            },

            applyModalVariantChange() {
                if (!this.barcodeModal.matchedVariant) return;
                const variant = this.barcodeModal.matchedVariant;
                const selections = { ...this.barcodeModal.variantSelections };
                const index = this.barcodeModal.rowIndex;
                const item = this.purchaseItems[index];

                const variantLabel = Object.entries(variant)
                    .filter(([k]) => k !== 'id')
                    .map(([k, v]) => `${k}: ${v}`)
                    .join(' | ');

                this.$set(item, 'variant_combination_id', variant.id);
                this.$set(item, 'variantLabel', variantLabel);
                this.$set(item, 'variantData', selections);
                this.$set(item, 'display_name', `${item.name} (${variantLabel})`);

                this.barcodeModal.item = item;
                this.barcodeModal.editingVariant = false;

                if (typeof toastr !== 'undefined') toastr.success('Variant updated!');
            },

            /* ══════════════════════════════════════════
               CLICK OUTSIDE DROPDOWN
            ══════════════════════════════════════════ */
            handleOutsideClick(e) {
                const wrap = this.$el.querySelector('.po-search-wrap');
                if (wrap && !wrap.contains(e.target)) this.dropdownVisible = false;
            },
        },

        watch: {
            searchQuery(val) {
                if (!val || val.trim() === '') {
                    this.searchResults = [];
                    this.dropdownVisible = false;
                }
            },
            selectedWarehouse() {
                this.cartoonsByRoom = {};
                this.purchaseItems.forEach(item => {
                    item.warehouse_room_id = '';
                    item.warehouse_cartoon_id = '';
                    item.cartoonOptions = [];
                });
                this.getRooms();
            },
        },

        mounted() {
            window.addEventListener('click', this.handleOutsideClick);
        },
        beforeDestroy() {
            window.removeEventListener('click', this.handleOutsideClick);
        },
    });
};