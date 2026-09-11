(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CustomerView = {
        name: 'PosV3CustomerView',
        template: `
            <div class="pos-v3-customer-view">
                <div v-if="viewCustomer" class="pos-v3-customer-view__content">
                    <section class="pos-v3-customer-view__card">
                        <h4 class="pos-v3-customer-view__card-title">Basic Information</h4>
                        <div class="pos-v3-customer-view__grid">
                            <div><strong>Name:</strong> {{ viewCustomer.name || 'N/A' }}</div>
                            <div><strong>Type:</strong> {{ viewCustomer.customer_type || 'person' }}</div>
                            <div v-if="viewCustomer.customer_type === 'company'"><strong>Company:</strong> {{ viewCustomer.company_name || 'N/A' }}</div>
                            <div v-if="viewCustomer.customer_type === 'company'"><strong>Trade Name:</strong> {{ viewCustomer.trade_name || 'N/A' }}</div>
                            <div><strong>Phone:</strong> {{ viewCustomer.phone || 'N/A' }}</div>
                            <div><strong>Email:</strong> {{ viewCustomer.email || 'N/A' }}</div>
                            <div class="pos-v3-customer-view__full"><strong>Address:</strong> {{ viewCustomer.address || 'N/A' }}</div>
                            <div><strong>Due Amount:</strong> <span class="pos-v3-customer-view__due">{{ formatMoney(viewCustomer.due_amount || 0) }}</span></div>
                            <div><strong>Available Advance:</strong> <span class="pos-v3-customer-view__advance">{{ formatMoney(viewCustomer.advance || viewCustomer.available_advance || 0) }}</span></div>
                        </div>
                    </section>

                    <section v-if="viewCustomer.customer_type === 'company'" class="pos-v3-customer-view__card">
                        <h4 class="pos-v3-customer-view__card-title">Contact Persons</h4>
                        <div v-if="viewCustomer.contact_persons && viewCustomer.contact_persons.length">
                            <div
                                v-for="contact in viewCustomer.contact_persons"
                                :key="'contact-view-' + contact.id"
                                class="pos-v3-customer-view__item">
                                <strong>{{ contact.name || 'N/A' }}</strong>
                                <span v-if="contact.is_primary" class="pos-v3-customer-view__badge">Primary</span>
                                <div class="pos-v3-customer-view__muted">
                                    {{ contact.designation || '' }}
                                    <span v-if="contact.department"> - {{ contact.department }}</span>
                                </div>
                                <div>{{ contact.phone || 'N/A' }}<span v-if="contact.email"> | {{ contact.email }}</span></div>
                            </div>
                        </div>
                        <div v-else class="pos-v3-customer-view__empty">No contact persons found</div>
                    </section>

                    <section class="pos-v3-customer-view__card">
                        <h4 class="pos-v3-customer-view__card-title">Billing Addresses</h4>
                        <div v-if="viewCustomer.billing_address && viewCustomer.billing_address.length">
                            <div
                                v-for="(addr, index) in viewCustomer.billing_address"
                                :key="'billing-view-' + index"
                                class="pos-v3-customer-view__item">
                                <div><strong>Full Name:</strong> {{ addr.full_name || 'N/A' }}</div>
                                <div><strong>Phone:</strong> {{ addr.phone || 'N/A' }}</div>
                                <div><strong>Address:</strong> {{ addr.address || 'N/A' }}</div>
                            </div>
                        </div>
                        <div v-else class="pos-v3-customer-view__empty">No billing addresses found</div>
                    </section>

                    <section class="pos-v3-customer-view__card">
                        <h4 class="pos-v3-customer-view__card-title">Shipping Addresses</h4>
                        <div v-if="viewCustomer.shipping_address && viewCustomer.shipping_address.length">
                            <div
                                v-for="(addr, index) in viewCustomer.shipping_address"
                                :key="'shipping-view-' + index"
                                class="pos-v3-customer-view__item">
                                <div><strong>Full Name:</strong> {{ addr.full_name || 'N/A' }}</div>
                                <div><strong>Phone:</strong> {{ addr.phone || 'N/A' }}</div>
                                <div><strong>Address:</strong> {{ addr.address || 'N/A' }}</div>
                            </div>
                        </div>
                        <div v-else class="pos-v3-customer-view__empty">No shipping addresses found</div>
                    </section>

                    <div class="pos-v3-customer-view__actions">
                        <button type="button" class="pos-v3-btn pos-v3-btn--accent" @click="editCustomer">
                            <i class="fas fa-edit"></i> Edit Customer
                        </button>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost" @click="selectCustomer">
                            Select Customer
                        </button>
                    </div>
                </div>

                <div v-else class="pos-v3-customer-view__loading">
                    {{ isLoading ? 'Loading customer...' : 'Customer not found.' }}
                </div>
            </div>
        `,
        computed: {
            customerStore: function () {
                return global.PosV3.useCustomerStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            viewCustomer: function () {
                return this.customerStore.viewCustomer;
            },
            isLoading: function () {
                return this.uiStore.loading.customerDetail;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            editCustomer: function () {
                if (this.viewCustomer) {
                    this.customerStore.openEditCustomer(this.viewCustomer);
                }
            },
            selectCustomer: function () {
                if (this.viewCustomer) {
                    this.customerStore.selectCustomer(this.viewCustomer);
                }
            },
        },
    };
})(window);
