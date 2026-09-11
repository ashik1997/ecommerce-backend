(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3DiscountRow = {
        name: 'PosV3DiscountRow',
        template: `
            <div class="pos-v3-totals-row">
                <div class="pos-v3-totals-row__left">
                    <span class="pos-v3-totals-label">Discount</span>
                    <div class="pos-v3-totals-controls">
                        <label>
                            <input type="radio" name="pos_v3_discount_type" value="fixed" :checked="discountType === 'fixed'" @change="setType('fixed')">
                            <span>Fixed</span>
                        </label>
                        <label>
                            <input type="radio" name="pos_v3_discount_type" value="percent" :checked="discountType === 'percent'" @change="setType('percent')">
                            <span>Percent</span>
                        </label>
                    </div>
                </div>
                <div class="pos-v3-totals-row__right">
                    <input type="text" class="pos-v3-totals-input" :value="discountValue" @change="setValue($event.target.value)">
                    <span class="pos-v3-totals-muted">= {{ formatMoney(discountAmount) }}</span>
                </div>
            </div>
        `,
        computed: {
            totalsStore: function () {
                return global.PosV3.useTotalsStore();
            },
            discountType: function () {
                return this.totalsStore.discount.type;
            },
            discountValue: function () {
                return this.totalsStore.discount.value;
            },
            discountAmount: function () {
                return this.totalsStore.discount.amount;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            setType: function (type) {
                this.totalsStore.setDiscountType(type);
            },
            setValue: function (value) {
                this.totalsStore.setDiscountValue(value);
            },
        },
    };
})(window);
