(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3AdvanceRow = {
        name: 'PosV3AdvanceRow',
        template: `
            <div class="pos-v3-totals-row pos-v3-payment-row" v-if="showAdvance">
                <span class="pos-v3-totals-row__left">
                    <label class="pos-v3-payment-check">
                        <input type="checkbox" :checked="useAdvance" @change="onCheckboxChange">
                        <span class="pos-v3-totals-label">Advance</span>
                        <span v-if="availableAdvance > 0" class="pos-v3-totals-muted">
                            (Available: {{ formatMoney(availableAdvance) }})
                        </span>
                    </label>
                </span>
                <span class="pos-v3-totals-row__right" v-if="useAdvance">
                    <input
                        type="text"
                        class="pos-v3-totals-input"
                        :value="advanceAmount"
                        @focus="onAdvanceFocus"
                        @input="onAdvanceInput"
                        @keyup.up.prevent="incrementAdvance"
                        @keyup.down.prevent="decrementAdvance">
                </span>
            </div>
        `,
        computed: {
            paymentStore: function () {
                return global.PosV3.usePaymentStore();
            },
            showAdvance: function () {
                return this.paymentStore.showAdvance;
            },
            useAdvance: function () {
                return this.paymentStore.useAdvance;
            },
            advanceAmount: function () {
                return this.paymentStore.advanceAmount;
            },
            availableAdvance: function () {
                return this.paymentStore.availableAdvance;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            onCheckboxChange: function (event) {
                this.paymentStore.useAdvance = event.target.checked;
                this.paymentStore.onAdvanceCheckboxChange();
            },
            onAdvanceInput: function (event) {
                this.paymentStore.updateAdvanceAmount(event);
            },
            onAdvanceFocus: function (event) {
                this.paymentStore.onAdvanceFocus(event);
            },
            incrementAdvance: function (event) {
                const max = this.availableAdvance;
                const next = Math.min(max, (parseFloat(this.advanceAmount) || 0) + 1);

                this.paymentStore.advanceAmount = next;
                this.paymentStore.updateAdvanceAmount({ target: { value: next } });
                event.target.value = next;
            },
            decrementAdvance: function (event) {
                const next = Math.max(0, (parseFloat(this.advanceAmount) || 0) - 1);

                this.paymentStore.advanceAmount = next;
                this.paymentStore.updateAdvanceAmount({ target: { value: next } });
                event.target.value = next;
            },
        },
    };
})(window);
