(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useCartStore = Pinia.defineStore('posV3Cart', {
        state: function () {
            return {
                items: [],
            };
        },
        getters: {
            itemCount: function (state) {
                return state.items.length;
            },
            subtotal: function (state) {
                return state.items.reduce(function (sum, item) {
                    return sum + Number(item.final_price || item.line_total || 0);
                }, 0);
            },
        },
        actions: {
            _triggerTotalsRecalc: function () {
                if (global.PosV3.useTotalsStore) {
                    global.PosV3.useTotalsStore().recalc();
                }
            },
            clear: function () {
                this.items = [];
                this._triggerTotalsRecalc();
            },
            setItems: function (items, options) {
                this.items = global.PosV3.cloneDeep(items || []);

                if (!options || !options.skipRecalc) {
                    this._triggerTotalsRecalc();
                }
            },
            addBuiltItem: function (builtItem) {
                if (!builtItem) {
                    return;
                }

                const orderStore = global.PosV3.useOrderStore();
                const pricing = global.PosV3.cartPricing;
                const cloneDeep = global.PosV3.cloneDeep;
                const existing = global.PosV3.cartItemBuilder.findExistingItem(this.items, builtItem);

                if (existing) {
                    const addQty = Math.max(1, parseInt(builtItem.qty, 10) || 1);
                    let nextQty = parseInt(existing.qty, 10);
                    if (!Number.isFinite(nextQty)) {
                        nextQty = Math.floor(Number(existing.qty) || 0);
                    }
                    nextQty += addQty;

                    let cap = parseInt(existing.max_qty, 10);
                    if (!Number.isFinite(cap)) {
                        cap = Math.floor(Number(existing.max_qty) || 0);
                    }
                    if (!Number.isFinite(cap) || cap <= 0) {
                        const fromItem = parseInt(builtItem.max_qty, 10);
                        if (Number.isFinite(fromItem) && fromItem > 0) {
                            cap = fromItem;
                        }
                    }
                    if (Number.isFinite(cap) && cap > 0 && nextQty > cap) {
                        nextQty = cap;
                    }

                    existing.qty = nextQty;
                    if (builtItem.unit_code && !existing.unit_code) {
                        existing.unit_code = builtItem.unit_code;
                    }
                    if (Number.isFinite(cap) && cap > 0) {
                        existing.max_qty = cap;
                    }

                    pricing.recalcCartItem(existing);
                    this._triggerTotalsRecalc();
                    return;
                }

                const item = Object.assign({}, cloneDeep(builtItem), {
                    temp_id: 'ci-' + Date.now() + '-' + Math.random().toString(36).slice(2),
                    qty: builtItem.qty || 1,
                    max_qty: builtItem.max_qty != null ? builtItem.max_qty : builtItem.stock || 0,
                    discount: cloneDeep(builtItem.discount) || {
                        type: 'fixed',
                        percent: 0,
                        fixed: 0,
                        value: 0,
                        amount: 0,
                    },
                });

                pricing.applyPricingForSelectedType(item, orderStore.selectedProductPriceType);
                this.items.push(item);
                this._triggerTotalsRecalc();
            },
            addFromProduct: function (product, variantPayload) {
                const builtItem = global.PosV3.cartItemBuilder.buildFromProduct(product, variantPayload);

                if (!builtItem) {
                    return;
                }

                if (variantPayload && variantPayload.qty != null) {
                    builtItem.qty = Math.max(1, parseInt(variantPayload.qty, 10) || 1);
                }

                this.addBuiltItem(builtItem);
            },
            removeItem: function (tempId) {
                this.items = this.items.filter(function (item) {
                    return item.temp_id !== tempId;
                });
                this._triggerTotalsRecalc();
            },
            updateItem: function (tempId, field, value) {
                const item = this.items.find(function (row) {
                    return row.temp_id === tempId;
                });

                if (!item) {
                    return;
                }

                if (field === 'qty') {
                    let qty = Math.max(0, Math.floor(Number(value) || 0));
                    const maxQty = Math.floor(Number(item.max_qty) || 0);
                    if (maxQty > 0 && qty > maxQty) {
                        qty = maxQty;
                    }
                    item.qty = qty;
                    global.PosV3.cartPricing.recalcCartItem(item);
                    this._triggerTotalsRecalc();
                    return;
                }

                if (field === 'unit_price') {
                    item.unit_price = Math.max(0, Number(value) || 0);
                    global.PosV3.cartPricing.recalcCartItem(item);
                    this._triggerTotalsRecalc();
                    return;
                }

                if (field === 'discount_percent') {
                    item.discount.type = 'percent';
                    item.discount.percent = Math.max(0, Number(value) || 0);
                    global.PosV3.cartPricing.recalcCartItem(item);
                    this._triggerTotalsRecalc();
                    return;
                }

                if (field === 'discount_fixed') {
                    item.discount.type = 'fixed';
                    item.discount.fixed = Math.round(Math.max(0, Number(value) || 0) / 5) * 5;
                    global.PosV3.cartPricing.recalcCartItem(item);
                    this._triggerTotalsRecalc();
                    return;
                }

                if (field === 'product_note') {
                    item.product_note = value || '';
                }
            },
            applyPriceTypeToAll: function () {
                const orderStore = global.PosV3.useOrderStore();
                const pricing = global.PosV3.cartPricing;

                this.items.forEach(function (item) {
                    pricing.applyPricingForSelectedType(item, orderStore.selectedProductPriceType);
                });
                this._triggerTotalsRecalc();
            },
        },
    });
})(window);
