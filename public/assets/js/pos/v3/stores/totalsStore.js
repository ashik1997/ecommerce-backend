(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useTotalsStore = Pinia.defineStore('posV3Totals', {
        state: function () {
            return {
                discount: {
                    type: 'percent',
                    value: 0,
                    amount: 0,
                },
                coupon: {
                    code: '',
                    type: '',
                    value: 0,
                    percent: 0,
                    amount: 0,
                },
                extraCharge: 0,
                useExtraChargeLines: false,
                extraChargeLines: [],
                deliveryCharge: 0,
                deliveryChargeType: '',
                roundOff: 0,
                subtotal: 0,
                grandTotal: 0,
            };
        },
        getters: {
            extraChargeLineTotal: function (state) {
                return global.PosV3.normalizeExtraChargeLines(state.extraChargeLines).reduce(function (sum, line) {
                    return sum + Math.max(0, Number(line.amount || 0));
                }, 0);
            },
            effectiveExtraCharge: function (state) {
                if (state.useExtraChargeLines || state.extraChargeLines.length) {
                    return this.extraChargeLineTotal;
                }

                return Number(state.extraCharge || 0);
            },
            totalsPayload: function (state) {
                return {
                    subtotal: state.subtotal,
                    discount: global.PosV3.cloneDeep(state.discount),
                    coupon: global.PosV3.cloneDeep(state.coupon),
                    extra_charge: this.effectiveExtraCharge,
                    extra_charge_lines: global.PosV3.normalizeExtraChargeLines(state.extraChargeLines),
                    delivery_charge: Number(state.deliveryCharge || 0),
                    round_off: Number(state.roundOff || 0),
                    grand_total: state.grandTotal,
                };
            },
        },
        actions: {
            recalc: function () {
                const cartStore = global.PosV3.useCartStore();

                this.applyDeliveryChargeByType(false);
                this.subtotal = cartStore.items.reduce(function (sum, item) {
                    return sum + Number(item.final_price || 0);
                }, 0);

                let discountAmount = 0;

                if (this.discount.type === 'percent') {
                    discountAmount = this.subtotal * (Number(this.discount.value || 0) / 100);
                }

                if (this.discount.type === 'fixed') {
                    discountAmount = Number(this.discount.value || 0);
                }

                discountAmount = Math.round(Math.max(0, Math.min(discountAmount, this.subtotal)));
                this.discount.amount = discountAmount;

                const afterOrderDiscount = this.subtotal - discountAmount;
                let couponAmount = 0;

                if (this.coupon.type === 'fixed') {
                    couponAmount = Number(this.coupon.value || 0);
                }

                if (this.coupon.type === 'percent') {
                    couponAmount = afterOrderDiscount * (Number(this.coupon.percent || 0) / 100);
                }

                couponAmount = Math.round(Math.max(0, Math.min(couponAmount, afterOrderDiscount)));
                this.coupon.amount = couponAmount;

                const extra = this.effectiveExtraCharge;
                if (this.useExtraChargeLines || this.extraChargeLines.length) {
                    this.extraCharge = extra;
                }

                this.grandTotal = Math.max(0, Math.round(
                    afterOrderDiscount -
                    couponAmount +
                    extra +
                    Number(this.deliveryCharge || 0) -
                    Number(this.roundOff || 0)
                ));
            },
            recalcFromServer: function () {
                const configStore = global.PosV3.useConfigStore();
                const cartStore = global.PosV3.useCartStore();
                const uiStore = global.PosV3.useUiStore();
                const url = configStore.routes.calculateTotals;

                if (!url) {
                    this.recalc();
                    return Promise.resolve();
                }

                this.recalc();

                uiStore.setLoading('totals', true);

                return global.PosV3.api.post(url, {
                    cart: global.PosV3.cloneDeep(cartStore.items),
                    discount: this.discount,
                    coupon: this.coupon,
                    extra_charge: this.effectiveExtraCharge,
                    extra_charge_lines: global.PosV3.normalizeExtraChargeLines(this.extraChargeLines),
                    delivery_charge: this.deliveryCharge,
                    round_off: this.roundOff,
                }).then(function (response) {
                    const data = response.data && response.data.data ? response.data.data : null;

                    if (!data) {
                        return;
                    }

                    this.subtotal = Number(data.subtotal || this.subtotal);
                    this.discount.amount = Number((data.discount && data.discount.amount) || this.discount.amount);
                    this.coupon.amount = Number((data.coupon && data.coupon.amount) || this.coupon.amount);
                    this.extraCharge = Number(data.extra_charge || this.extraCharge);
                    this.deliveryCharge = Number(data.delivery_charge || this.deliveryCharge);
                    this.roundOff = Number(data.round_off || this.roundOff);
                    this.grandTotal = Number(data.grand_total || this.grandTotal);
                }.bind(this)).catch(function () {
                    this.recalc();
                }.bind(this)).finally(function () {
                    uiStore.setLoading('totals', false);
                });
            },
            setDiscountType: function (type) {
                this.discount.type = type === 'fixed' ? 'fixed' : 'percent';
                this.recalc();
            },
            setDiscountValue: function (value) {
                this.discount.value = Math.max(0, Number(value) || 0);
                this.recalc();
            },
            setExtraCharge: function (value) {
                if (this.useExtraChargeLines || this.extraChargeLines.length) {
                    return;
                }

                this.extraCharge = Math.max(0, Number(value) || 0);
                this.recalc();
            },
            setExtraChargeLines: function (lines) {
                this.extraChargeLines = global.PosV3.normalizeExtraChargeLines(lines);

                if (this.useExtraChargeLines || this.extraChargeLines.length) {
                    this.useExtraChargeLines = true;
                    this.extraCharge = this.extraChargeLineTotal;
                }

                this.recalc();
            },
            setUseExtraChargeLines: function (enabled) {
                this.useExtraChargeLines = !!enabled;

                if (this.useExtraChargeLines) {
                    this.extraCharge = this.extraChargeLineTotal;
                }

                this.recalc();
            },
            setRoundOff: function (value) {
                const parsed = value === '' || value === '-' ? 0 : Number(value);
                this.roundOff = isNaN(parsed) ? 0 : parsed;
                this.recalc();
            },
            setDeliveryCharge: function (value) {
                this.deliveryCharge = Math.max(0, Number(value) || 0);
                this.recalc();
            },
            setDeliveryChargeType: function (type, updateAmount) {
                this.deliveryChargeType = type || '';

                if (!this.deliveryChargeType) {
                    this.deliveryCharge = 0;
                    this.recalc();
                    return;
                }

                if (updateAmount) {
                    this.applyDeliveryChargeByType(true);
                }

                this.recalc();
            },
            applyDeliveryChargeByType: function (updateAmount) {
                if (!this.deliveryChargeType) {
                    this.deliveryCharge = 0;
                    return;
                }

                if (!updateAmount) {
                    return;
                }

                const cartStore = global.PosV3.useCartStore();
                const weights = cartStore.items.map(function (item) {
                    return (Number(item.weight || 0.5) * Number(item.qty || 0));
                });

                this.deliveryCharge = global.PosV3.deliveryCharge.getSteadfastDeliveryCharge(
                    this.deliveryChargeType === 'inside_city',
                    weights
                );
            },
            applyCoupon: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const url = configStore.routes.applyCoupon;

                if (!url || !this.coupon.code) {
                    return Promise.resolve();
                }

                uiStore.setLoading('coupon', true);

                return global.PosV3.api.post(url, {
                    code: this.coupon.code,
                    subtotal: this.subtotal,
                }).then(function (response) {
                    if (!response.data || !response.data.success) {
                        throw new Error((response.data && response.data.message) || 'Invalid coupon');
                    }

                    const data = response.data.data || {};
                    this.coupon.type = data.type === 'percent' ? 'percent' : 'fixed';
                    this.coupon.value = Number(data.value) || 0;
                    this.coupon.percent = data.type === 'percent' ? this.coupon.value : 0;
                    this.recalc();
                }.bind(this)).catch(function (error) {
                    const message = error.response && error.response.data && error.response.data.message
                        ? error.response.data.message
                        : (error.message || 'Coupon apply failed');

                    window.alert(message);
                }).finally(function () {
                    uiStore.setLoading('coupon', false);
                });
            },
            reset: function () {
                this.discount = { type: 'percent', value: 0, amount: 0 };
                this.coupon = { code: '', type: '', value: 0, percent: 0, amount: 0 };
                this.extraCharge = 0;
                this.useExtraChargeLines = false;
                this.extraChargeLines = [];
                this.deliveryCharge = 0;
                this.deliveryChargeType = '';
                this.roundOff = 0;
                this.subtotal = 0;
                this.grandTotal = 0;
            },
            applySnapshot: function (snapshot) {
                if (!snapshot) {
                    return;
                }

                this.subtotal = Number(snapshot.subtotal || 0);
                this.discount = Object.assign(
                    { type: 'percent', value: 0, amount: 0 },
                    global.PosV3.cloneDeep(snapshot.discount || {})
                );
                this.coupon = Object.assign(
                    { code: '', type: '', value: 0, percent: 0, amount: 0 },
                    global.PosV3.cloneDeep(snapshot.coupon || {})
                );
                this.extraCharge = Number(snapshot.extra_charge || 0);
                this.extraChargeLines = global.PosV3.normalizeExtraChargeLines(snapshot.extra_charge_lines || []);
                this.useExtraChargeLines = this.extraChargeLines.length > 0;
                this.deliveryCharge = Number(snapshot.delivery_charge || 0);
                this.deliveryChargeType = snapshot.delivery_charge_type || '';
                this.roundOff = Number(snapshot.round_off || 0);
                this.grandTotal = Number(snapshot.grand_total || 0);
            },
        },
    });
})(window);
