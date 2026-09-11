(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CashExchange = {
        name: 'PosV3CashExchange',
        template: `
            <div class="pos-v3-cash-exchange" v-if="showCashExchange">
                <div class="pos-v3-cash-exchange__header">
                    <span class="pos-v3-cash-exchange__icon" aria-hidden="true">
                        <i class="fas fa-coins"></i>
                    </span>
                    <div class="pos-v3-cash-exchange__heading">
                        <strong>Cash Change</strong>
                        <span class="pos-v3-cash-exchange__hint">Due {{ formatMoney(totalCashPayment) }}</span>
                    </div>
                </div>

                <div class="pos-v3-cash-exchange__body">
                    <label class="pos-v3-cash-exchange__field">
                        <span class="pos-v3-cash-exchange__label">Cash received</span>
                        <input
                            type="text"
                            class="pos-v3-cash-exchange__input"
                            inputmode="decimal"
                            :value="cashReceived"
                            placeholder="0.00"
                            @focus="onFocus"
                            @input="onInput">
                    </label>

                    <div
                        class="pos-v3-cash-exchange__change"
                        :class="{ 'is-active': exchangeAmount > 0 }">
                        <span class="pos-v3-cash-exchange__label">Return change</span>
                        <strong class="pos-v3-cash-exchange__amount">{{ formatMoney(exchangeAmount) }}</strong>
                    </div>
                </div>
            </div>
        `,
        computed: {
            paymentStore: function () {
                return global.PosV3.usePaymentStore();
            },
            showCashExchange: function () {
                return this.paymentStore.showCashExchange;
            },
            cashReceived: function () {
                return this.paymentStore.cashReceived;
            },
            exchangeAmount: function () {
                return this.paymentStore.exchangeAmount;
            },
            totalCashPayment: function () {
                return this.paymentStore.totalCashPayment;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            onInput: function (event) {
                this.paymentStore.setCashReceived(event.target.value);
            },
            onFocus: function (event) {
                if (typeof event.target.select === 'function') {
                    event.target.select();
                }
            },
        },
    };
})(window);
