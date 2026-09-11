(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3DeliveryRow = {
        name: 'PosV3DeliveryRow',
        template: `
            <div class="pos-v3-totals-row">
                <div class="pos-v3-totals-row__left">
                    <span class="pos-v3-totals-label">Delivery Charge</span>
                    <div class="pos-v3-totals-controls">
                        <label>
                            <input type="radio" name="pos_v3_delivery_type" value="inside_city" :checked="deliveryType === 'inside_city'" @change="setType('inside_city', true)">
                            <span>Inside</span>
                        </label>
                        <label>
                            <input type="radio" name="pos_v3_delivery_type" value="outside_city" :checked="deliveryType === 'outside_city'" @change="setType('outside_city', true)">
                            <span>Outside</span>
                        </label>
                    </div>
                </div>
                <div class="pos-v3-totals-row__right">
                    <input type="text" class="pos-v3-totals-input" :value="deliveryCharge" @change="setCharge($event.target.value)">
                </div>
            </div>
        `,
        computed: {
            totalsStore: function () {
                return global.PosV3.useTotalsStore();
            },
            deliveryType: function () {
                return this.totalsStore.deliveryChargeType;
            },
            deliveryCharge: function () {
                return this.totalsStore.deliveryCharge;
            },
        },
        methods: {
            setType: function (type, updateAmount) {
                this.totalsStore.setDeliveryChargeType(type, updateAmount);
            },
            setCharge: function (value) {
                this.totalsStore.setDeliveryCharge(value);
            },
        },
    };
})(window);
