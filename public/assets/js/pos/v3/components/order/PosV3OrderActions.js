(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3OrderActions = {
        name: 'PosV3OrderActions',
        template: `
            <div class="pos-v3-order-actions" :class="{ 'is-loading': isSubmitting }">
                <div class="pos-v3-order-actions__field">
                    <label class="pos-v3-label" for="pos-v3-order-note">Order Note</label>
                    <textarea
                        id="pos-v3-order-note"
                        class="pos-v3-textarea"
                        rows="2"
                        placeholder="Optional note or instruction"
                        :value="orderNote"
                        @input="setNote($event.target.value)">
                    </textarea>
                </div>


                <div class="pos-v3-order-actions__field">
                    <label class="pos-v3-label" for="pos-v3-salesman">Salesman / SR</label>
                    <select id="pos-v3-salesman" class="pos-v3-input" :value="salesmanId || ''" @change="setSalesman($event.target.value)">
                        <option value="">Current User / Default</option>
                        <option v-for="user in salesUsers" :key="user.id" :value="user.id">
                            {{ user.name }}{{ user.phone ? ' — ' + user.phone : '' }}
                        </option>
                    </select>
                </div>

                <div class="pos-v3-order-actions__field">
                    <label class="pos-v3-label" for="pos-v3-affiliate-code">Affiliate Code / Reference</label>
                    <input
                        id="pos-v3-affiliate-code"
                        class="pos-v3-input"
                        list="pos-v3-affiliate-codes"
                        placeholder="Example: RAHIM10"
                        :value="affiliateCode"
                        @input="setAffiliateCode($event.target.value)">
                    <datalist id="pos-v3-affiliate-codes">
                        <option v-for="affiliate in affiliates" :key="affiliate.id" :value="affiliate.code">{{ affiliate.name }}</option>
                    </datalist>
                </div>

                <div class="pos-v3-order-actions__status">
                    <span class="pos-v3-label">Order Status</span>
                    <div class="pos-v3-order-actions__radios">
                        <label v-for="option in statusOptions" :key="option.value">
                            <input
                                type="radio"
                                name="pos_v3_order_status"
                                :value="option.value"
                                :checked="orderStatus === option.value"
                                @change="setOrderStatus(option.value)">
                            <span>{{ option.label }}</span>
                        </label>
                    </div>
                </div>

                <label class="pos-v3-order-actions__sms">
                    <input type="checkbox" :checked="smsSendToCustomer" @change="setSmsSend($event.target.checked)">
                    <span>Send SMS to Customer</span>
                </label>

                <div class="pos-v3-delivery-info-container" v-if="deliveryFeatureEnabled">
                    <label class="pos-v3-order-actions__delivery-toggle">
                        <input type="checkbox" v-model="deliveryInfoOpen">
                        <span>Delivery Info</span>
                    </label>
                    <div class="pos-v3-delivery-info-wrapper" v-if="deliveryInfoOpen">
                        <pos-v3-delivery-info-panel></pos-v3-delivery-info-panel>
                    </div>
                </div>

                <pos-v3-cash-exchange></pos-v3-cash-exchange>

                <div class="pos-v3-order-actions__buttons">
                    <button
                        type="button"
                        class="pos-v3-btn pos-v3-btn--ghost"
                        :disabled="isSubmitting || !canPreview"
                        @click="previewOrder">
                        Preview
                    </button>
                    <button
                        type="button"
                        class="pos-v3-btn pos-v3-btn--success"
                        :disabled="isSubmitting || !canSubmit"
                        @click="submitOrder">
                        {{ submitLabel }}
                    </button>
                </div>
            </div>
        `,
        data: function () {
            return {
                deliveryInfoOpen: false,
                statusOptions: [
                    { value: 'pending', label: 'Quotation' },
                    { value: 'invoiced', label: 'Invoiced' },
                    { value: 'delivered', label: 'Delivered' },
                ],
            };
        },
        computed: {
            configStore: function () {
                return global.PosV3.useConfigStore();
            },
            deliveryFeatureEnabled: function () {
                return !!this.configStore.features.delivery;
            },
            orderStore: function () {
                return global.PosV3.useOrderStore();
            },
            cartStore: function () {
                return global.PosV3.useCartStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            orderNote: function () {
                return this.orderStore.note;
            },
            orderStatus: function () {
                return this.orderStore.orderStatus;
            },
            smsSendToCustomer: function () {
                return this.orderStore.smsSendToCustomer;
            },
            salesUsers: function () {
                return this.configStore.salesUsers || [];
            },
            affiliates: function () {
                return this.configStore.affiliates || [];
            },
            salesmanId: function () {
                return this.orderStore.salesmanId;
            },
            affiliateCode: function () {
                return this.orderStore.affiliateCode || '';
            },
            isSubmitting: function () {
                return this.uiStore.loading.submit;
            },
            isEditMode: function () {
                return this.orderStore.isEditMode;
            },
            submitLabel: function () {
                if (this.isSubmitting) {
                    return this.isEditMode ? 'Updating...' : 'Submitting...';
                }

                return this.isEditMode ? 'Update Order' : 'Submit Order';
            },
            canSubmit: function () {
                return this.cartStore.items.length > 0;
            },
            canPreview: function () {
                return this.cartStore.items.length > 0;
            },
        },
        methods: {
            setNote: function (value) {
                this.orderStore.setNote(value);
            },
            setOrderStatus: function (value) {
                this.orderStore.setOrderStatus(value);
            },
            setSmsSend: function (value) {
                this.orderStore.setSmsSendToCustomer(value);
            },
            submitOrder: function () {
                this.orderStore.submitOrder();
            },
            previewOrder: function () {
                this.orderStore.previewOrder();
            },
        },
    };
})(window);
