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
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken && window.axios) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
    }

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
                warehouseModal: {
                    open: false,
                    mode: 'list',
                    loading: false,
                    saving: false,
                    error: '',
                    items: Array.isArray(window.purchaseWarehouses) ? window.purchaseWarehouses : [],
                    form: {
                        id: null,
                        title: '',
                        address: '',
                        description: '',
                        status: 'active',
                    },
                },

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
                    unitDetails: [],
                    hasSerial: false,
                    hasImei: false,
                    hasWarranty: false,
                    serialPrefix: '',
                    serialStart: 1,
                    commonWarrantyStart: '',
                    commonWarrantyEnd: '',
                    commonWarrantyNote: '',
                    scanEntry: '',
                    commonBarcode: '',
                    editingVariant: false,
                    variantSelections: {},
                    matchedVariant: null,
                },
            };
        },

        computed: {
            activeWarehouseItems() {
                return this.warehouseModal.items.filter(warehouse => (warehouse.status || 'active') === 'active');
            },
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
                    const clientErrors = this.validatePurchaseItems();
                    if (clientErrors.length) {
                        this.formErrors = clientErrors;
                        this.formSubmitting = false;
                        if (typeof toastr !== 'undefined' && toastr && typeof toastr.error === 'function') {
                            toastr.error(clientErrors[0]);
                        }
                        return;
                    }

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

            validatePurchaseItems() {
                const errors = [];
                const barcodeProducts = {};

                if (!this.purchaseItems.length) {
                    return ['Please add at least one product to the purchase order.'];
                }

                this.purchaseItems.forEach((item, index) => {
                    const rowNo = index + 1;
                    const qty = Number(item.quantity || 0);
                    const price = Number(item.price || 0);

                    if (!qty || qty < 1) errors.push(`Row ${rowNo}: quantity must be at least 1.`);
                    if (!price || price <= 0) errors.push(`Row ${rowNo}: unit price must be greater than 0.`);

                    const details = this.normalizeUnitDetails(item, Math.max(0, parseInt(qty) || 0), item.barcodes || []);
                    details.forEach(unit => {
                        const barcode = (unit.barcode || '').toString().trim();
                        if (!barcode) return;
                        const productId = (item.product_id || item.id || '').toString();
                        barcodeProducts[barcode] = barcodeProducts[barcode] || {};
                        barcodeProducts[barcode][productId] = true;
                    });
                });

                Object.keys(barcodeProducts).forEach(barcode => {
                    if (Object.keys(barcodeProducts[barcode]).length > 1) {
                        errors.push(`Barcode is assigned to multiple products in this purchase: ${barcode}.`);
                    }
                });

                return errors;
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

                if (product.has_variants) {
                    if (typeof toastr !== 'undefined') toastr.info('Select a variant, then add it to the order.');
                    return;
                }

                this.addSelectedProductToOrder(product);
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
                    const exists = this.purchaseItems.find(i =>
                        i.product_id === product.id &&
                        Number(i.variant_combination_id) === Number(this.pendingMatchedVariant.id)
                    );
                    if (exists) {
                        if (typeof toastr !== 'undefined') toastr.info('Variant already added.');
                        return;
                    }
                    const row = this.createRow(product, this.pendingMatchedVariant, this.pendingVariantSelections);
                    this.purchaseItems.push(row);

                    if (typeof toastr !== 'undefined') toastr.success('Variant added to order.');

                    // Keep the selected product active so the user can add more variants one-by-one.
                    this.pendingVariantSelections = {};
                    this.pendingMatchedVariant = null;
                    return;
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

            clearPendingProduct() {
                this.pendingProduct = null;
                this.pendingVariantSelections = {};
                this.pendingMatchedVariant = null;
                this.searchQuery = '';
                this.searchResults = [];
                this.dropdownVisible = false;
            },

            addSelectedProductToOrder(product) {
                if (!product) return;

                if (product.has_variants) {
                    this.addAllProductVariantsToTable(product);
                } else {
                    const exists = this.purchaseItems.find(i => i.product_id === product.id && !i.variant_combination_id);
                    if (exists) {
                        if (typeof toastr !== 'undefined') toastr.info('Product already added.');
                        return;
                    }
                    this.purchaseItems.push(this.createRow(product, null, {}));
                    if (typeof toastr !== 'undefined') toastr.success('Product added to order.');
                }

                this.pendingProduct = null;
                this.pendingVariantSelections = {};
                this.pendingMatchedVariant = null;
            },

            addAllProductVariantsToTable(product) {
                const combinations = Array.isArray(product.product_variant_combinations)
                    ? product.product_variant_combinations
                    : [];

                if (!combinations.length) {
                    if (typeof toastr !== 'undefined') toastr.warning('No variant combinations found for this product.');
                    return;
                }

                let addedCount = 0;
                combinations.forEach(combination => {
                    const exists = this.purchaseItems.find(i =>
                        i.product_id === product.id &&
                        Number(i.variant_combination_id) === Number(combination.id)
                    );
                    if (exists) return;

                    this.purchaseItems.push(this.createRow(
                        product,
                        combination,
                        this.getSelectionsFromCombination(combination)
                    ));
                    addedCount++;
                });

                if (typeof toastr !== 'undefined') {
                    if (addedCount > 0) {
                        toastr.success(`${addedCount} variant${addedCount > 1 ? 's' : ''} added to order.`);
                    } else {
                        toastr.info('All variants already added.');
                    }
                }
            },

            getSelectionsFromCombination(combination) {
                const skipKeys = ['id', 'price', 'discount_price'];
                return Object.keys(combination || {}).reduce((selections, key) => {
                    if (!skipKeys.includes(key)) selections[key] = combination[key];
                    return selections;
                }, {});
            },

            createRow(product, matchedVariant, selections) {
                const isVariant = !!matchedVariant;
                const variantLabel = isVariant
                    ? Object.entries(matchedVariant)
                        .filter(([k]) => !['id', 'price', 'discount_price'].includes(k))
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
                    price: Number(
                        isVariant
                            ? (matchedVariant.discount_price || matchedVariant.price || product.unit_price || product.main_price || product.price || 0)
                            : (product.unit_price || product.main_price || product.price || 0)
                    ),
                    quantity: 0,
                    discount: 0,
                    tax: 0,
                    previous_stock: Number(product.stock || 0),
                    warehouse_room_id: '',
                    warehouse_cartoon_id: '',
                    cartoonOptions: [],
                    barcodes: [],
                    unit_details: [],
                    imei: '',
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

                if (field === 'quantity') {
                    let qty = parseInt(item.quantity) || 0;

                    if (!Array.isArray(item.imei)) {
                        item.imei = [];
                    }

                    if (item.imei.length < qty) {
                        for (let i = item.imei.length; i < qty; i++) {
                            item.imei.push('');
                        }
                    } else if (item.imei.length > qty) {
                        item.imei.splice(qty);
                    }
                }
                if (raw === '' || raw === null || raw === undefined) {
                    this.$set(item, field, '');
                    if (field === 'quantity') {
                        this.$set(item, 'barcodes', []);
                        this.$set(item, 'unit_details', []);
                    }
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
                item.unit_details = this.normalizeUnitDetails(item, qty, item.barcodes);
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
            resetWarehouseForm() {
                this.warehouseModal.form = {
                    id: null,
                    title: '',
                    address: '',
                    description: '',
                    status: 'active',
                };
                this.warehouseModal.error = '';
            },

            setWarehouseMode(mode) {
                this.warehouseModal.mode = mode;
                this.warehouseModal.error = '';
                if (mode === 'create') this.resetWarehouseForm();
                if (mode === 'list') this.loadWarehouses();
            },

            openWarehouseModal() {
                this.warehouseModal.open = true;
                this.warehouseModal.mode = 'list';
                this.warehouseModal.error = '';
                this.loadWarehouses();
            },

            closeWarehouseModal() {
                this.warehouseModal.open = false;
                this.warehouseModal.error = '';
            },

            loadWarehouses() {
                this.warehouseModal.loading = true;
                return axios.get('/api/product-warehouses', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                }).then(res => {
                    this.warehouseModal.items = (res.data && res.data.data) ? res.data.data : [];
                }).catch(error => {
                    this.warehouseModal.error = this.getWarehouseErrorMessage(error, 'Unable to load warehouses.');
                }).finally(() => {
                    this.warehouseModal.loading = false;
                });
            },

            editWarehouse(warehouse) {
                this.warehouseModal.form = {
                    id: warehouse.id,
                    title: warehouse.title || '',
                    address: warehouse.address || '',
                    description: warehouse.description || '',
                    status: warehouse.status || 'active',
                };
                this.warehouseModal.mode = 'edit';
                this.warehouseModal.error = '';
            },

            saveWarehouse() {
                if (this.warehouseModal.saving) return;
                if (!this.validateWarehouseForm()) return;

                this.warehouseModal.saving = true;
                axios.post('/api/product-warehouses', this.warehouseModal.form, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                }).then(res => {
                    const warehouse = res.data.data;
                    this.warehouseModal.items.unshift(warehouse);
                    this.selectedWarehouse = warehouse.id;
                    this.getRooms();
                    this.resetWarehouseForm();
                    this.warehouseModal.mode = 'list';
                    if (typeof toastr !== 'undefined') toastr.success(res.data.message || 'Warehouse created successfully.');
                }).catch(error => {
                    this.warehouseModal.error = this.getWarehouseErrorMessage(error, 'Unable to create warehouse.');
                }).finally(() => {
                    this.warehouseModal.saving = false;
                });
            },

            updateWarehouse() {
                if (this.warehouseModal.saving) return;
                if (!this.validateWarehouseForm()) return;

                const id = this.warehouseModal.form.id;
                this.warehouseModal.saving = true;
                axios.post(`/api/product-warehouses/${id}`, this.warehouseModal.form, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                }).then(res => {
                    const warehouse = res.data.data;
                    const index = this.warehouseModal.items.findIndex(item => Number(item.id) === Number(warehouse.id));
                    if (index > -1) this.$set(this.warehouseModal.items, index, warehouse);
                    if (Number(this.selectedWarehouse) === Number(warehouse.id) && warehouse.status !== 'active') {
                        this.selectedWarehouse = '';
                        this.getRooms();
                    }
                    this.warehouseModal.mode = 'list';
                    if (typeof toastr !== 'undefined') toastr.success(res.data.message || 'Warehouse updated successfully.');
                }).catch(error => {
                    this.warehouseModal.error = this.getWarehouseErrorMessage(error, 'Unable to update warehouse.');
                }).finally(() => {
                    this.warehouseModal.saving = false;
                });
            },

            deleteWarehouse(warehouse) {
                if (!warehouse || !warehouse.id) return;
                if (!confirm('Are you sure you want to delete this warehouse?')) return;

                axios.delete(`/api/product-warehouses/${warehouse.id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                }).then(res => {
                    this.warehouseModal.items = this.warehouseModal.items.filter(item => Number(item.id) !== Number(warehouse.id));
                    if (Number(this.selectedWarehouse) === Number(warehouse.id)) {
                        this.selectedWarehouse = '';
                        this.getRooms();
                    }
                    if (typeof toastr !== 'undefined') toastr.success(res.data.message || 'Warehouse deleted successfully.');
                }).catch(error => {
                    this.warehouseModal.error = this.getWarehouseErrorMessage(error, 'Unable to delete warehouse.');
                });
            },

            validateWarehouseForm() {
                this.warehouseModal.error = '';
                if (!(this.warehouseModal.form.title || '').trim()) {
                    this.warehouseModal.error = 'Warehouse title is required.';
                    return false;
                }
                return true;
            },

            getWarehouseErrorMessage(error, fallback) {
                if (error.response && error.response.status === 422 && error.response.data && error.response.data.errors) {
                    const errors = error.response.data.errors;
                    const firstKey = Object.keys(errors)[0];
                    return firstKey ? errors[firstKey][0] : fallback;
                }
                if (error.response && error.response.data && error.response.data.message) {
                    return error.response.data.message;
                }
                return fallback;
            },

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
                const unitDetails = this.normalizeUnitDetails(item, qty, barcodes);
                const modalFlags = this.getUnitDetailFlags(item, unitDetails);

                this.barcodeModal = {
                    open: true,
                    rowIndex: index,
                    item: item,
                    product: item.productRef || null,
                    qty: qty,
                    barcodes: barcodes,
                    unitDetails: unitDetails,
                    hasSerial: modalFlags.hasSerial,
                    hasImei: modalFlags.hasImei,
                    hasWarranty: modalFlags.hasWarranty,
                    serialPrefix: '',
                    serialStart: 1,
                    commonWarrantyStart: '',
                    commonWarrantyEnd: '',
                    commonWarrantyNote: '',
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
                const unitDetails = this.normalizeModalUnitDetails(qty);
                const barcodes = unitDetails.map(unit => unit.barcode || '');

                this.$set(item, 'quantity', qty);
                this.$set(item, 'barcodes', barcodes);
                this.$set(item, 'unit_details', unitDetails);

                this.closeBarcodeModal();
            },

            clearModalBarcodes() {
                this.barcodeModal.qty = 0;
                this.barcodeModal.barcodes = [];
                this.barcodeModal.unitDetails = [];
                this.barcodeModal.hasSerial = false;
                this.barcodeModal.hasImei = false;
                this.barcodeModal.hasWarranty = false;
                this.barcodeModal.serialPrefix = '';
                this.barcodeModal.serialStart = 1;
                this.barcodeModal.commonWarrantyStart = '';
                this.barcodeModal.commonWarrantyEnd = '';
                this.barcodeModal.commonWarrantyNote = '';
                this.barcodeModal.scanEntry = '';
                this.barcodeModal.commonBarcode = '';
            },

            addModalBarcode() {
                const trimmed = (this.barcodeModal.scanEntry || '').toString().trim();
                if (!trimmed) return;
                this.barcodeModal.unitDetails.push(this.blankUnitDetail(trimmed));
                this.barcodeModal.barcodes = this.barcodeModal.unitDetails.map(unit => unit.barcode || '');
                this.barcodeModal.qty = this.barcodeModal.unitDetails.length;
                this.barcodeModal.scanEntry = '';
            },

            onModalQtyChange() {
                let qty = parseInt(this.barcodeModal.qty) || 0;
                if (qty < 0) qty = 0;
                this.barcodeModal.qty = qty;

                if (qty === 0) {
                    this.barcodeModal.barcodes = [];
                    this.barcodeModal.unitDetails = [];
                    return;
                }
                while (this.barcodeModal.unitDetails.length < qty) {
                    this.barcodeModal.unitDetails.push(this.blankUnitDetail(''));
                }
                if (this.barcodeModal.unitDetails.length > qty) this.barcodeModal.unitDetails.splice(qty);
                this.barcodeModal.barcodes = this.barcodeModal.unitDetails.map(unit => unit.barcode || '');
            },

            removeModalBarcode(index) {
                this.barcodeModal.unitDetails.splice(index, 1);
                this.barcodeModal.barcodes = this.barcodeModal.unitDetails.map(unit => unit.barcode || '');
                this.barcodeModal.qty = this.barcodeModal.unitDetails.length;
            },

            applyCommonModalBarcode() {
                const code = (this.barcodeModal.commonBarcode || '').toString().trim();
                if (!code || !this.barcodeModal.unitDetails.length) return;
                this.barcodeModal.unitDetails = this.barcodeModal.unitDetails.map(unit => ({
                    ...this.blankUnitDetail(''),
                    ...unit,
                    barcode: code,
                }));
                this.barcodeModal.barcodes = this.barcodeModal.unitDetails.map(unit => unit.barcode || '');
            },

            generateSerialSequence() {
                const prefix = (this.barcodeModal.serialPrefix || '').toString();
                const start = parseInt(this.barcodeModal.serialStart, 10) || 1;
                this.barcodeModal.unitDetails = this.barcodeModal.unitDetails.map((unit, index) => ({
                    ...this.blankUnitDetail(''),
                    ...unit,
                    serial_no: `${prefix}${start + index}`,
                }));
            },

            applyCommonWarranty() {
                this.barcodeModal.unitDetails = this.barcodeModal.unitDetails.map(unit => ({
                    ...this.blankUnitDetail(''),
                    ...unit,
                    supplier_warranty_start_date: this.barcodeModal.commonWarrantyStart || unit.supplier_warranty_start_date || '',
                    supplier_warranty_end_date: this.barcodeModal.commonWarrantyEnd || unit.supplier_warranty_end_date || '',
                    warranty_note: this.barcodeModal.commonWarrantyNote || unit.warranty_note || '',
                }));
            },

            blankUnitDetail(barcode = '') {
                return {
                    barcode: barcode,
                    serial_no: '',
                    imei_1: '',
                    imei_2: '',
                    supplier_warranty_start_date: '',
                    supplier_warranty_end_date: '',
                    warranty_note: '',
                };
            },

            normalizeUnitDetails(item, qty, barcodes = []) {
                const existing = Array.isArray(item.unit_details) ? item.unit_details : [];
                const details = [];
                for (let i = 0; i < qty; i++) {
                    details.push({
                        ...this.blankUnitDetail(barcodes[i] || ''),
                        ...(existing[i] || {}),
                        barcode: (existing[i] && existing[i].barcode !== undefined) ? existing[i].barcode : (barcodes[i] || ''),
                    });
                }
                return details;
            },

            normalizeModalUnitDetails(qty) {
                const existing = Array.isArray(this.barcodeModal.unitDetails) ? this.barcodeModal.unitDetails : [];
                const details = [];
                for (let i = 0; i < qty; i++) {
                    details.push({
                        ...this.blankUnitDetail(''),
                        ...(existing[i] || {}),
                    });
                }
                return details;
            },

            getUnitDetailFlags(item, unitDetails = []) {
                const flags = item.unit_detail_flags || {};
                return {
                    hasSerial: !!flags.hasSerial || unitDetails.some(unit => !!unit.serial_no),
                    hasImei: !!flags.hasImei || unitDetails.some(unit => !!unit.imei_1 || !!unit.imei_2),
                    hasWarranty: !!flags.hasWarranty || unitDetails.some(unit =>
                        !!unit.supplier_warranty_start_date ||
                        !!unit.supplier_warranty_end_date ||
                        !!unit.warranty_note
                    ),
                };
            },

            updateUnitDetailFlag(flag, value) {
                const index = this.barcodeModal.rowIndex;
                if (index === null || index === undefined) return;
                const item = this.purchaseItems[index];
                if (!item) return;

                const current = item.unit_detail_flags || {};
                this.$set(item, 'unit_detail_flags', {
                    ...current,
                    [flag]: !!value,
                });
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
