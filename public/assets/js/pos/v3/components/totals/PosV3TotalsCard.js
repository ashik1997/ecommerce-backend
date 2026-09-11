(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3TotalsCard = {
        name: 'PosV3TotalsCard',
        template: `
            <div class="pos-v3-totals-card" :class="{ 'is-loading': isLoading }">
                <div class="pos-v3-totals-row pos-v3-totals-row--subtotal">
                    <span>Subtotal</span>
                    <strong>{{ formatMoney(subtotal) }}</strong>
                </div>

                <div class="pos-v3-totals-section">
                    <pos-v3-discount-row></pos-v3-discount-row>
                    <pos-v3-coupon-row></pos-v3-coupon-row>
                </div>

                <div class="pos-v3-totals-section">
                    <pos-v3-extra-charge-row></pos-v3-extra-charge-row>
                </div>

                <div class="pos-v3-totals-section">
                    <pos-v3-delivery-row></pos-v3-delivery-row>
                    <pos-v3-round-off-row></pos-v3-round-off-row>
                </div>

                <pos-v3-grand-total></pos-v3-grand-total>
            </div>
        `,
        computed: {
            totalsStore: function () {
                return global.PosV3.useTotalsStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            subtotal: function () {
                return this.totalsStore.subtotal;
            },
            isLoading: function () {
                return this.uiStore.loading.totals;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
        },
    };
})(window);
