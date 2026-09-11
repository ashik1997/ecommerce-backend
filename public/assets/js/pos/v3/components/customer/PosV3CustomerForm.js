(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CustomerForm = {
        name: 'PosV3CustomerForm',
        template: `
            <form class="pos-v3-customer-form" @submit.prevent="save">
                <h3 class="pos-v3-customer-form__title">{{ isEdit ? 'Update Customer' : 'Add Customer' }}</h3>

                <div class="pos-v3-customer-form__grid">
                    <label class="pos-v3-customer-form__field pos-v3-customer-form__field--full">
                        <span>Name <span class="pos-v3-customer-form__required">*</span></span>
                        <input type="text" class="pos-v3-input" v-model="formCustomer.name" required>
                    </label>

                    <label class="pos-v3-customer-form__field">
                        <span>Customer Type</span>
                        <select class="pos-v3-input" v-model="formCustomer.customer_type">
                            <option value="person">Person</option>
                            <option value="company">Company</option>
                        </select>
                    </label>

                    <template v-if="formCustomer.customer_type === 'company'">
                        <div class="pos-v3-customer-form__section pos-v3-customer-form__field--full">
                            <label class="pos-v3-customer-form__field">
                                <span>Company Name</span>
                                <input type="text" class="pos-v3-input" v-model="formCustomer.company_name">
                            </label>
                            <label class="pos-v3-customer-form__field">
                                <span>Trade Name</span>
                                <input type="text" class="pos-v3-input" v-model="formCustomer.trade_name">
                            </label>
                            <div class="pos-v3-customer-form__grid pos-v3-customer-form__grid--2">
                                <label class="pos-v3-customer-form__field">
                                    <span>BIN</span>
                                    <input type="text" class="pos-v3-input" v-model="formCustomer.bin_no">
                                </label>
                                <label class="pos-v3-customer-form__field">
                                    <span>TIN</span>
                                    <input type="text" class="pos-v3-input" v-model="formCustomer.tin_no">
                                </label>
                            </div>
                        </div>
                    </template>

                    <label class="pos-v3-customer-form__field">
                        <span>Phone <span class="pos-v3-customer-form__required">*</span></span>
                        <input type="text" class="pos-v3-input" v-model="formCustomer.phone" required>
                    </label>

                    <label class="pos-v3-customer-form__field">
                        <span>Email</span>
                        <input type="email" class="pos-v3-input" v-model="formCustomer.email">
                    </label>

                    <label class="pos-v3-customer-form__field pos-v3-customer-form__field--full">
                        <span>Address</span>
                        <textarea class="pos-v3-input" rows="2" v-model="formCustomer.address"></textarea>
                    </label>

                    <label class="pos-v3-customer-form__field">
                        <span>Customer Source</span>
                        <select class="pos-v3-input" v-model="formCustomer.customer_source_type_id">
                            <option value="">Select Customer Source</option>
                            <option v-for="source in customerSources" :key="source.id" :value="source.id">{{ source.title }}</option>
                        </select>
                    </label>

                    <label class="pos-v3-customer-form__field">
                        <span>Credit Limit</span>
                        <input type="number" min="0" step="0.01" class="pos-v3-input" v-model.number="formCustomer.credit_limit">
                    </label>

                    <label class="pos-v3-customer-form__field">
                        <span>Payment Terms (days)</span>
                        <input type="number" min="0" class="pos-v3-input" v-model.number="formCustomer.payment_terms_days">
                    </label>

                    <label class="pos-v3-customer-form__checkbox">
                        <input type="checkbox" v-model="formCustomer.allow_due">
                        Allow Due
                    </label>
                </div>

                <div v-if="formCustomer.customer_type === 'company'" class="pos-v3-customer-form__section">
                    <div class="pos-v3-customer-form__section-head">
                        <h4>Contact Persons</h4>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" @click="addContactPerson">
                            <i class="fas fa-plus"></i> Add Contact
                        </button>
                    </div>
                    <div
                        v-for="(contact, index) in contactPersons"
                        :key="'contact-' + index"
                        class="pos-v3-customer-form__card">
                        <div class="pos-v3-customer-form__grid pos-v3-customer-form__grid--2">
                            <label class="pos-v3-customer-form__field">
                                <span>Name</span>
                                <input type="text" class="pos-v3-input" v-model="contact.name">
                            </label>
                            <label class="pos-v3-customer-form__field">
                                <span>Designation</span>
                                <input type="text" class="pos-v3-input" v-model="contact.designation">
                            </label>
                            <label class="pos-v3-customer-form__field">
                                <span>Phone</span>
                                <input type="text" class="pos-v3-input" v-model="contact.phone">
                            </label>
                            <label class="pos-v3-customer-form__field">
                                <span>Email</span>
                                <input type="email" class="pos-v3-input" v-model="contact.email">
                            </label>
                            <label class="pos-v3-customer-form__field">
                                <span>Department</span>
                                <input type="text" class="pos-v3-input" v-model="contact.department">
                            </label>
                        </div>
                        <div class="pos-v3-customer-form__card-actions">
                            <label class="pos-v3-customer-form__checkbox">
                                <input type="checkbox" v-model="contact.is_primary" @change="setPrimaryContact(index)">
                                Primary Contact
                            </label>
                            <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm pos-v3-btn--danger" @click="removeContactPerson(index)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pos-v3-customer-form__section">
                    <label class="pos-v3-customer-form__checkbox">
                        <input type="checkbox" v-model="saveAsUser">
                        Save as User
                    </label>
                    <label v-if="saveAsUser" class="pos-v3-customer-form__field pos-v3-customer-form__field--full">
                        <span>Password <span class="pos-v3-customer-form__required">*</span></span>
                        <input type="password" class="pos-v3-input" v-model="password" placeholder="Enter password" required>
                    </label>
                </div>

                <div class="pos-v3-customer-form__section">
                    <div class="pos-v3-customer-form__section-head">
                        <h4>Billing Address</h4>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" @click="addBillingAddress">
                            <i class="fas fa-plus"></i> Add Billing Address
                        </button>
                    </div>
                    <div
                        v-for="(billingAddress, index) in billingAddresses"
                        :key="'billing-' + index"
                        class="pos-v3-customer-form__card">
                        <label class="pos-v3-customer-form__field">
                            <span>Full Name</span>
                            <input type="text" class="pos-v3-input" v-model="billingAddress.full_name">
                        </label>
                        <label class="pos-v3-customer-form__field">
                            <span>Phone</span>
                            <input type="text" class="pos-v3-input" v-model="billingAddress.phone">
                        </label>
                        <label class="pos-v3-customer-form__field">
                            <span>Address</span>
                            <textarea class="pos-v3-input" rows="2" v-model="billingAddress.address"></textarea>
                        </label>
                        <label class="pos-v3-customer-form__field">
                            <span>District</span>
                            <select class="pos-v3-input" v-model="billingAddress.district">
                                <option :value="null">Select District</option>
                                <option v-for="district in districts" :key="'billing-d-' + district.district_id" :value="district">
                                    {{ district.division_name }} &gt; {{ district.district_name }}
                                </option>
                            </select>
                        </label>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm pos-v3-btn--danger" @click="removeBillingAddress(index)">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>

                <div class="pos-v3-customer-form__section">
                    <div class="pos-v3-customer-form__section-head">
                        <h4>Shipping Address</h4>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" @click="addShippingAddress">
                            <i class="fas fa-plus"></i> Add Shipping Address
                        </button>
                    </div>
                    <div
                        v-for="(shippingAddress, index) in shippingAddresses"
                        :key="'shipping-' + index"
                        class="pos-v3-customer-form__card">
                        <label class="pos-v3-customer-form__field">
                            <span>Full Name</span>
                            <input type="text" class="pos-v3-input" v-model="shippingAddress.full_name">
                        </label>
                        <label class="pos-v3-customer-form__field">
                            <span>Phone</span>
                            <input type="text" class="pos-v3-input" v-model="shippingAddress.phone">
                        </label>
                        <label class="pos-v3-customer-form__field">
                            <span>Address</span>
                            <textarea class="pos-v3-input" rows="2" v-model="shippingAddress.address"></textarea>
                        </label>
                        <label class="pos-v3-customer-form__field">
                            <span>District</span>
                            <select class="pos-v3-input" v-model="shippingAddress.district">
                                <option :value="null">Select District</option>
                                <option v-for="district in districts" :key="'shipping-d-' + district.district_id" :value="district">
                                    {{ district.division_name }} &gt; {{ district.district_name }}
                                </option>
                            </select>
                        </label>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm pos-v3-btn--danger" @click="removeShippingAddress(index)">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>

                <div class="pos-v3-customer-form__actions">
                    <button type="submit" class="pos-v3-btn pos-v3-btn--accent" :disabled="isSaving">
                        {{ isSaving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </form>
        `,
        computed: {
            customerStore: function () {
                return global.PosV3.useCustomerStore();
            },
            deliveryOptionsStore: function () {
                return global.PosV3.useDeliveryOptionsStore();
            },
            formCustomer: function () {
                return this.customerStore.formCustomer;
            },
            billingAddresses: function () {
                return this.customerStore.billingAddresses;
            },
            shippingAddresses: function () {
                return this.customerStore.shippingAddresses;
            },
            contactPersons: function () {
                return this.customerStore.contactPersons;
            },
            districts: function () {
                return this.customerStore.districts;
            },
            customerSources: function () {
                return this.deliveryOptionsStore.customerSources;
            },
            saveAsUser: {
                get: function () {
                    return this.customerStore.saveAsUser;
                },
                set: function (value) {
                    this.customerStore.saveAsUser = value;
                },
            },
            password: {
                get: function () {
                    return this.customerStore.password;
                },
                set: function (value) {
                    this.customerStore.password = value;
                },
            },
            isEdit: function () {
                return this.customerStore.modalMode === 'edit';
            },
            isSaving: function () {
                return global.PosV3.useUiStore().loading.customerSave;
            },
        },
        methods: {
            save: function () {
                this.customerStore.saveCustomerFull();
            },
            addBillingAddress: function () {
                this.customerStore.addBillingAddress();
            },
            removeBillingAddress: function (index) {
                this.customerStore.removeBillingAddress(index);
            },
            addShippingAddress: function () {
                this.customerStore.addShippingAddress();
            },
            removeShippingAddress: function (index) {
                this.customerStore.removeShippingAddress(index);
            },
            addContactPerson: function () {
                this.customerStore.addContactPerson();
            },
            removeContactPerson: function (index) {
                this.customerStore.removeContactPerson(index);
            },
            setPrimaryContact: function (index) {
                this.customerStore.setPrimaryContact(index);
            },
        },
    };
})(window);
