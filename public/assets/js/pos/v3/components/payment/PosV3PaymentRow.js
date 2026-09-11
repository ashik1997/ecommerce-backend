(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3PaymentRow = {
        name: 'PosV3PaymentRow',
        props: {
            method: {
                type: Object,
                required: true,
            },
            variant: {
                type: String,
                default: 'default',
            },
            removable: {
                type: Boolean,
                default: false,
            },
        },
        emits: ['remove'],
        template: `
            <div class="pos-v3-payment-entry pos-v3-payment-row" :class="entryClasses">
                <div class="pos-v3-payment-entry__meta">
                    <span class="pos-v3-payment-entry__title">{{ method.title }}</span>
                    <span v-if="accountLabel" class="pos-v3-payment-entry__account">{{ accountLabel }}</span>
                </div>
                <div class="pos-v3-payment-entry__amount">
                    <input
                        type="text"
                        class="pos-v3-totals-input"
                        :value="method.amount"
                        :max="maxAmount"
                        @focus="onFocus"
                        @input="onInput"
                        @keyup.up.prevent="increment"
                        @keyup.down.prevent="decrement">
                </div>
                <button
                    v-if="removable"
                    type="button"
                    class="pos-v3-payment-entry__remove"
                    @click="$emit('remove', method)">
                    Remove
                </button>
            </div>
        `,
        computed: {
            paymentStore: function () {
                return global.PosV3.usePaymentStore();
            },
            maxAmount: function () {
                return this.paymentStore.getPaymentMaxAmount(this.method);
            },
            accountLabel: function () {
                return this.method.account_name || '';
            },
            entryClasses: function () {
                return {
                    'pos-v3-payment-entry--cash': this.variant === 'cash',
                    'pos-v3-payment-entry--selected': this.variant === 'selected',
                    'pos-v3-payment-entry--default': this.variant === 'default',
                };
            },
        },
        methods: {
            onFocus: function (event) {
                this.paymentStore.onPaymentFocus(event, this.method);
            },
            onInput: function (event) {
                this.paymentStore.updatePaymentValue(event, this.method);
            },
            increment: function (event) {
                this.paymentStore.incrementPaymentValue(event, this.method);
            },
            decrement: function (event) {
                this.paymentStore.decrementPaymentValue(event, this.method);
            },
        },
    };
})(window);
