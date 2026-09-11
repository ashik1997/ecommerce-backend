(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3DeliveryInfoPanel = {
        name: 'PosV3DeliveryInfoPanel',
        template: `
            <div class="pos-v3-delivery-info">
                <div class="pos-v3-delivery-info__field">
                    <label class="pos-v3-label" for="pos-v3-delivery-method">Delivery Method</label>
                    <select
                        id="pos-v3-delivery-method"
                        class="pos-v3-select"
                        :value="deliveryInfo.delivery_method"
                        @change="setField('delivery_method', $event.target.value)">
                        <option value="">Select Delivery Method</option>
                        <option v-for="method in deliveryMethods" :key="method.id" :value="method.title">
                            {{ method.title }}
                        </option>
                    </select>
                </div>

                <div class="pos-v3-delivery-info__field" v-if="showOutletSelect">
                    <label class="pos-v3-label" for="pos-v3-outlet">Outlet</label>
                    <select
                        id="pos-v3-outlet"
                        class="pos-v3-select"
                        :value="deliveryInfo.outlet_id"
                        @change="setField('outlet_id', $event.target.value)">
                        <option value="">Select Outlet</option>
                        <option v-for="outlet in outlets" :key="outlet.id" :value="outlet.id">
                            {{ outlet.title }}
                        </option>
                    </select>
                </div>

                <div class="pos-v3-delivery-info__field">
                    <label class="pos-v3-label" for="pos-v3-expected-date">Expected Delivery Date</label>
                    <input
                        id="pos-v3-expected-date"
                        type="date"
                        class="pos-v3-input"
                        :value="deliveryInfo.expected_delivery_date"
                        @input="setField('expected_delivery_date', $event.target.value)">
                </div>

                <div class="pos-v3-delivery-info__field">
                    <label class="pos-v3-label" for="pos-v3-order-source">Order Source</label>
                    <select
                        id="pos-v3-order-source"
                        class="pos-v3-select"
                        :value="deliveryInfo.order_source"
                        @change="setField('order_source', $event.target.value)">
                        <option value="">Select Order Source</option>
                        <option v-for="source in customerSources" :key="source.id" :value="source.id">
                            {{ source.title }}
                        </option>
                    </select>
                </div>

                <div class="pos-v3-delivery-info__field">
                    <label class="pos-v3-label" for="pos-v3-delivery-note">Delivery Note</label>
                    <textarea
                        id="pos-v3-delivery-note"
                        class="pos-v3-textarea"
                        rows="2"
                        :value="deliveryInfo.order_note"
                        @input="setField('order_note', $event.target.value)">
                    </textarea>
                </div>

                <div class="pos-v3-delivery-info__field">
                    <span class="pos-v3-label">Courier Method</span>
                    <div class="pos-v3-delivery-info__radios">
                        <label>
                            <input
                                type="radio"
                                name="pos_v3_courier_method"
                                value=""
                                :checked="!deliveryInfo.courier_method"
                                @change="clearCourier">
                            <span>None</span>
                        </label>
                        <label v-for="courier in courierMethods" :key="courier.id">
                            <input
                                type="radio"
                                name="pos_v3_courier_method"
                                :value="courier.id"
                                :checked="String(deliveryInfo.courier_method) === String(courier.id)"
                                @change="setCourier(courier)">
                            <span>{{ courier.title }}</span>
                        </label>
                    </div>
                </div>

                <div class="pos-v3-delivery-info__field" v-if="deliveryInfo.courier_method_title">
                    <label class="pos-v3-label" for="pos-v3-courier-address">Courier Address</label>
                    <textarea
                        id="pos-v3-courier-address"
                        class="pos-v3-textarea"
                        rows="2"
                        :value="customerAddress"
                        @input="setCustomerAddress($event.target.value)">
                    </textarea>
                </div>
            </div>
        `,
        computed: {
            configStore: function () {
                return global.PosV3.useConfigStore();
            },
            orderStore: function () {
                return global.PosV3.useOrderStore();
            },
            optionsStore: function () {
                return global.PosV3.useDeliveryOptionsStore();
            },
            customerStore: function () {
                return global.PosV3.useCustomerStore();
            },
            deliveryInfo: function () {
                return this.orderStore.deliveryInfo;
            },
            deliveryMethods: function () {
                return this.optionsStore.deliveryMethods;
            },
            outlets: function () {
                return this.optionsStore.outlets;
            },
            courierMethods: function () {
                return this.optionsStore.courierMethods;
            },
            customerSources: function () {
                return this.optionsStore.customerSources;
            },
            showOutletSelect: function () {
                const method = String(this.deliveryInfo.delivery_method || '').toLowerCase();

                return method === 'store_pickup' || method === 'store pickup';
            },
            customerAddress: function () {
                return this.customerStore.selectedCustomer && this.customerStore.selectedCustomer.address
                    ? this.customerStore.selectedCustomer.address
                    : '';
            },
        },
        methods: {
            setField: function (key, value) {
                this.orderStore.setDeliveryInfoField(key, value);
            },
            setCourier: function (courier) {
                this.orderStore.setCourierMethod(courier);
            },
            clearCourier: function () {
                this.orderStore.setCourierMethod(null);
            },
            setCustomerAddress: function (value) {
                if (this.customerStore.selectedCustomer) {
                    this.customerStore.selectedCustomer.address = value || '';
                }
            },
        },
    };
})(window);
