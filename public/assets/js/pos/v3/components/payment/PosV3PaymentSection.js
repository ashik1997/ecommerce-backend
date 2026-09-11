(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3PaymentSection = {
        name: 'PosV3PaymentSection',
        template: `
            <div class="pos-v3-payment-section" :class="{ 'is-loading': isLoading }">
                <div class="pos-v3-payment-section__header">
                    <h3>Payment</h3>
                </div>

                <pos-v3-payment-row
                    v-if="cashMethod"
                    :method="cashMethod"
                    variant="cash">
                </pos-v3-payment-row>

                <label v-if="otherPaymentMethods.length" class="pos-v3-payment-toggle">
                    <input type="checkbox" v-model="showOtherMethods">
                    <span>Other methods</span>
                </label>

                <div v-if="showOtherMethods && otherPaymentMethods.length" class="pos-v3-payment-other">
                    <div class="pos-v3-payment-tabs" role="tablist" aria-label="Payment method categories">
                        <button
                            v-for="tab in paymentTabs"
                            :key="tab.value"
                            type="button"
                            class="pos-v3-payment-tab"
                            :class="{ 'is-active': activePaymentCategory === tab.value }"
                            @click="setPaymentCategory(tab.value)">
                            {{ tab.label }}
                        </button>
                    </div>

                    <div class="pos-v3-payment-method-grid">
                        <button
                            v-for="method in filteredOtherPaymentMethods"
                            :key="method.id"
                            type="button"
                            class="pos-v3-payment-method-card"
                            :class="{ 'is-active': isPaymentMethodActive(method) }"
                            @click="openPaymentMethod(method)">
                            <span class="pos-v3-payment-method-card__title">{{ method.title }}</span>
                            <span class="pos-v3-payment-method-card__account">{{ paymentAccountLabel(method) }}</span>
                        </button>
                    </div>

                    <div v-if="selectedOtherPaymentMethods.length" class="pos-v3-payment-selected">
                        <pos-v3-payment-row
                            v-for="method in selectedOtherPaymentMethods"
                            :key="'selected-' + method.id"
                            :method="method"
                            variant="selected"
                            removable
                            @remove="removePaymentMethod">
                        </pos-v3-payment-row>
                    </div>
                    <div v-else class="pos-v3-payment-empty">
                        Select a payment method to add amount.
                    </div>
                </div>

                <pos-v3-advance-row></pos-v3-advance-row>
                <pos-v3-paid-due-summary></pos-v3-paid-due-summary>
            </div>
        `,
        data: function () {
            return {
                showOtherMethods: false,
                activePaymentCategory: 'all',
                activePaymentIds: [],
                paymentTabs: [
                    { value: 'all', label: 'All' },
                    { value: 'mobile', label: 'Mobile' },
                    { value: 'bank', label: 'Bank' },
                    { value: 'gateway', label: 'Gateway' },
                ],
            };
        },
        computed: {
            paymentStore: function () {
                return global.PosV3.usePaymentStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            paymentMethods: function () {
                return this.paymentStore.methods.filter(function (method) {
                    return method.selected;
                });
            },
            cashMethod: function () {
                return this.paymentMethods.find(function (method) {
                    return this.isCashMethod(method);
                }.bind(this));
            },
            otherPaymentMethods: function () {
                return this.paymentMethods.filter(function (method) {
                    return !this.isCashMethod(method);
                }.bind(this));
            },
            filteredOtherPaymentMethods: function () {
                if (this.activePaymentCategory === 'all') {
                    return this.otherPaymentMethods;
                }

                return this.otherPaymentMethods.filter(function (method) {
                    return this.paymentCategory(method) === this.activePaymentCategory;
                }.bind(this));
            },
            selectedOtherPaymentMethods: function () {
                return this.filteredOtherPaymentMethods.filter(function (method) {
                    return this.isPaymentMethodActive(method);
                }.bind(this));
            },
            isLoading: function () {
                return this.uiStore.loading.payments;
            },
        },
        methods: {
            isCashMethod: function (method) {
                return /^cash$/i.test(String(method.title || '').trim())
                    || String(method.id || '').toLowerCase() === 'cash';
            },
            setPaymentCategory: function (category) {
                this.activePaymentCategory = category;
            },
            paymentCategory: function (method) {
                const searchable = [
                    method.title || '',
                    method.account_name || '',
                ].join(' ').toLowerCase();

                if (/(bkash|bikash|nagad|rocket|upay|surecash|tap|mfs|mobile|wallet|cellfin)/i.test(searchable)) {
                    return 'mobile';
                }

                if (/(gateway|ssl|stripe|paypal|payoneer|visa|mastercard|amex|card|online)/i.test(searchable)) {
                    return 'gateway';
                }

                if (/(bank|dbbl|dutch|city|brac|eastern|islami|sonali|janata|agrani|account|cheque|check|transfer)/i.test(searchable)) {
                    return 'bank';
                }

                return 'bank';
            },
            paymentAccountLabel: function (method) {
                if (method.account_name) {
                    return method.account_name;
                }

                if (method.account_id) {
                    return 'Account #' + method.account_id;
                }

                return 'Default account';
            },
            isPaymentMethodActive: function (method) {
                return Number(method.amount || 0) > 0
                    || this.activePaymentIds.indexOf(String(method.id)) !== -1;
            },
            openPaymentMethod: function (method) {
                const methodId = String(method.id);

                if (this.activePaymentIds.indexOf(methodId) === -1) {
                    this.activePaymentIds.push(methodId);
                }
            },
            removePaymentMethod: function (method) {
                const methodId = String(method.id);

                this.paymentStore.updatePaymentValue({ target: { value: 0 } }, method);
                this.activePaymentIds = this.activePaymentIds.filter(function (activeId) {
                    return activeId !== methodId;
                });
            },
        },
    };
})(window);
