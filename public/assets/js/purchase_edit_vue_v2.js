/**
 * purchase_edit_vue_v2.js
 * Purchase Order Edit — Vue 2 app
 * Loads existing order items from the API on mount, then behaves
 * identically to purchase_vue_v2.js for add / edit / remove rows.
 */

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

window.onload = function () {
    /* ── pull config injected by blade ── */
    var EDIT_DATA = window.PURCHASE_EDIT_DATA || {};

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
                selectedWarehouse: EDIT_DATA.warehouse_id || '',
                rooms: [],
                cartoonsByRoom: {},

                /* ── purchase rows ── */
                purchaseItems: [],

                /* ── totals ── */
                other_charges_amt: 0,

                /* ── loading state ── */
                loadingOrder: false,

                /* ── form submit ── */
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
            /* ══════════════════════════════════════════
               FORM SUBMIT
            ══════════════════════════════════════════ */
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
                        if (typeof toastr !== 'undefined') {
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
                        if (typeof toastr !== 'undefined') {
                            toastr.success(data.message || 'Purchase updated successfully!');
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
                        this.formErrors = ['An unexpected error occurred while updating the purchase.'];
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.error(this.formErrors[0] || 'Error updating purchase');
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
               LOAD EXISTING ORDER ITEMS (on mount)
            ══════════════════════════════════════════ */
            async loadExistingOrder() {
                var slug = EDIT_DATA.slug;
                if (!slug) return;

                this.loadingOrder = true;
                try {
                    var res = await axios.get('/api/edit/purchase-product/order/' + slug);
                    var order = res.data && res.data.data;
                    if (!order) return;

                    /* ── ensure rooms are loaded first ── */
                    if (this.selectedWarehouse) {
                        await this.loadRoomsAsync(this.selectedWarehouse);
                    }

                    var orderProducts = order.order_products || [];
                    for (var i = 0; i < orderProducts.length; i++) {
                        var op = orderProducts[i];
                        var row = this.buildRowFromOrderProduct(op);

                        /* ── pre-load cartoon options if room set ── */
                        if (row.warehouse_room_id) {
                            var cartoons = await this.loadCartoonsForRoom(row.warehouse_room_id, true);
                            row.cartoonOptions = cartoons;
                        }

                        this.purchaseItems.push(row);
                    }
                } catch (e) {
                    console.error('Failed to load existing order:', e);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Could not load existing order items.');
                    }
                } finally {
                    this.loadingOrder = false;
                }
            },

            buildRowFromOrderProduct(op) {
                var vc = op.variant_combination || op.variantCombination || null;
                var variantLabel = '';

                if (op.variant_combination_id && vc) {
                    /* Build label from variant_values if available */
                    var values = vc.variant_values;
                    if (values && typeof values === 'object' && !Array.isArray(values)) {
                        variantLabel = Object.entries(values)
                            .map(function (entry) { return entry[0] + ': ' + entry[1]; })
                            .join(' | ');
                    } else if (vc.combination_key) {
                        variantLabel = vc.combination_key;
                    }
                }

                var displayName = productName = op.product_name || (op.product && op.product.name) || '';
                // var displayName = (op.variant_combination_id && variantLabel)
                //     ? productName + ' (' + variantLabel + ')'
                //     : productName;

                /*
                 * Load barcodes from the individual unit records (source of truth).
                 * The API returns op.units[] — each with a `code` field.
                 * Fall back to the JSON barcodes column only if no units were found
                 * (covers orders created before the units table existed).
                 */
                var barcodes = [];
                var unitDetails = [];
                var units = op.units || [];

                if (units.length > 0) {
                    barcodes = units
                        .map(function (u) { return u.code ? String(u.code).trim() : ''; })
                        .filter(function (c) { return c !== ''; });
                    unitDetails = units.map(function (u) {
                        return {
                            barcode: u.code || '',
                            serial_no: u.serial_no || '',
                            imei_1: u.imei_1 || '',
                            imei_2: u.imei_2 || '',
                            supplier_warranty_start_date: u.supplier_warranty_start_date ? String(u.supplier_warranty_start_date).slice(0, 10) : '',
                            supplier_warranty_end_date: u.supplier_warranty_end_date ? String(u.supplier_warranty_end_date).slice(0, 10) : '',
                            warranty_note: u.warranty_note || '',
                        };
                    });
                } else {
                    /* Fallback: parse the JSON barcodes column */
                    try {
                        var raw = op.barcodes;
                        if (Array.isArray(raw)) {
                            barcodes = raw;
                        } else if (typeof raw === 'string' && raw) {
                            barcodes = JSON.parse(raw) || [];
                        }
                        barcodes = barcodes.filter(function (b) { return b && String(b).trim() !== ''; });
                    } catch (e) {
                        barcodes = [];
                    }
                }
                var unitDetailFlags = this.getUnitDetailFlags({ unit_detail_flags: {} }, unitDetails);

                /* qty should reflect the number of units when they exist */
                var qty = units.length > 0 ? units.length : parseFloat(op.qty || 0);

                return {
                    rowKey: 'existing-' + op.id + '-' + Date.now() + '-' + Math.random(),
                    product_id: op.product_id,
                    id: op.product_id,
                    name: productName,
                    display_name: displayName,
                    variantLabel: variantLabel,
                    variant_combination_id: op.variant_combination_id || null,
                    variantData: null,
                    productRef: op.product || null,
                    price: parseFloat(op.product_price || 0),
                    quantity: qty,
                    discount: parseFloat(op.discount_amount || 0),
                    tax: parseFloat(op.tax || 0),
                    previous_stock: parseFloat(op.previous_stock || 0),
                    warehouse_room_id: op.product_warehouse_room_id || '',
                    warehouse_cartoon_id: op.product_warehouse_room_cartoon_id || '',
                    cartoonOptions: [],
                    barcodes: barcodes,
                    unit_details: unitDetails,
                    unit_detail_flags: unitDetailFlags,
                };
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
                axios.get('/stock-adjustment/search-products', {
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
                    const exists = this.purchaseItems.find(i => i.product_id === product.id && !i.variant_combination_id);
                    if (exists) {
                        if (typeof toastr !== 'undefined') toastr.info('Product already added.');
                        return;
                    }
                    const row = this.createRow(product, null, {});
                    this.purchaseItems.push(row);
                }

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
                    unit_details: [],
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

            loadRoomsAsync(warehouseId) {
                if (!warehouseId) return Promise.resolve([]);
                return axios.get(`/api/get-rooms/${warehouseId}`)
                    .then(res => {
                        this.rooms = res.data || [];
                        return this.rooms;
                    })
                    .catch(err => { console.error('Rooms error:', err); return []; });
            },

            loadCartoonsForRoom(roomId, silent) {
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
                while (barcodes.length < qty) barcodes.push('');
                if (barcodes.length > qty) barcodes.splice(qty);
                const unitDetails = this.normalizeUnitDetails(item, qty, barcodes);

                this.barcodeModal = {
                    open: true,
                    rowIndex: index,
                    item: item,
                    product: item.productRef || null,
                    qty: qty,
                    barcodes: barcodes,
                    unitDetails: unitDetails,
                    ...this.getUnitDetailFlags(item, unitDetails),
                    serialPrefix: '',
                    serialStart: 1,
                    commonWarrantyStart: '',
                    commonWarrantyEnd: '',
                    commonWarrantyNote: '',
                    scanEntry: '',
                    commonBarcode: '',
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
                const qty = this.barcodeModal.qty;
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
            /* Load existing order data after DOM ready */
            this.loadExistingOrder();
        },

        beforeDestroy() {
            window.removeEventListener('click', this.handleOutsideClick);
        },
    });
};
