(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CouponRow = {
        name: 'PosV3CouponRow',
        template: `
            <div class="pos-v3-totals-row">
                <div class="pos-v3-totals-row__left">
                    <span class="pos-v3-totals-label">Coupon</span>
                    <div class="pos-v3-totals-controls">
                        <span v-if="couponMeta" class="pos-v3-totals-muted">{{ couponMeta }}</span>
                        <input type="text" class="pos-v3-totals-code" v-model="couponCode" placeholder="Code" :disabled="isLoading">
                        <button type="button" class="pos-v3-btn pos-v3-btn--accent" :disabled="isLoading" @click="applyCoupon">
                            {{ isLoading ? '...' : 'Apply' }}
                        </button>
                    </div>
                </div>
                <div class="pos-v3-totals-row__right">
                    <span>{{ formatMoney(couponAmount) }}</span>
                </div>
            </div>
        `,
        computed: {
            totalsStore: function () {
                return global.PosV3.useTotalsStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            couponCode: {
                get: function () {
                    return this.totalsStore.coupon.code;
                },
                set: function (value) {
                    this.totalsStore.coupon.code = value || '';
                },
            },
            couponAmount: function () {
                return this.totalsStore.coupon.amount;
            },
            isLoading: function () {
                return this.uiStore.loading.coupon;
            },
            couponMeta: function () {
                if (!this.totalsStore.coupon.amount || !this.totalsStore.coupon.type) {
                    return '';
                }

                if (this.totalsStore.coupon.type === 'percent') {
                    return '(per: ' + this.totalsStore.coupon.percent + '%)';
                }

                return '(fix: ' + this.formatMoney(this.totalsStore.coupon.value) + ')';
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            applyCoupon: function () {
                this.totalsStore.applyCoupon();
            },
        },
    };
})(window);
