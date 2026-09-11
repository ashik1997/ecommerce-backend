(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3PaidDueSummary = {
        name: 'PosV3PaidDueSummary',
        template: `
            <div class="pos-v3-payment-summary">
                <div class="pos-v3-totals-row pos-v3-totals-row--grand pos-v3-payment-summary__row">
                    <span><strong>Paid Amount</strong></span>
                    <strong>{{ formatMoney(paymentTotal) }}</strong>
                </div>
                <div class="pos-v3-totals-row pos-v3-totals-row--grand pos-v3-payment-summary__row" :class="{ 'is-due': dueAmount > 0 }">
                    <span><strong>Due Amount</strong></span>
                    <strong>{{ formatMoney(dueAmount) }}</strong>
                </div>
            </div>
        `,
        computed: {
            paymentStore: function () {
                return global.PosV3.usePaymentStore();
            },
            paymentTotal: function () {
                return this.paymentStore.paymentTotal;
            },
            dueAmount: function () {
                return this.paymentStore.dueAmount;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
        },
    };
})(window);
