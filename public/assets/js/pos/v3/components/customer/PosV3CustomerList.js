(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CustomerList = {
        name: 'PosV3CustomerList',
        template: `
            <div class="pos-v3-customer-list">
                <div class="pos-v3-customer-modal__body" :class="{ 'is-loading': isLoading }">
                    <table class="pos-v3-customer-modal__table" v-if="customers.length">
                        <thead>
                            <tr>
                                <th class="pos-v3-customer-modal__col-select"></th>
                                <th>Name</th>
                                <th>Orders</th>
                                <th>Due</th>
                                <th>Bal</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th class="pos-v3-customer-modal__col-actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="customer in customers"
                                :key="customer.id"
                                :class="{ 'is-selected': isSelected(customer) }">
                                <td>
                                    <input
                                        type="radio"
                                        name="pos_v3_selected_customer"
                                        :checked="isSelected(customer)"
                                        @change="selectCustomer(customer)">
                                </td>
                                <td>
                                    <div>{{ customer.name || '—' }}</div>
                                    <small
                                        v-if="customer.customer_type === 'company'"
                                        class="pos-v3-customer-list__sub">
                                        {{ customer.company_name || customer.trade_name }}
                                    </small>
                                </td>
                                <td>{{ customer.order_count || 0 }}</td>
                                <td>{{ formatMoney(customer.due_amount || 0) }}</td>
                                <td>{{ formatMoney(customer.available_advance || customer.advance || 0) }}</td>
                                <td>{{ customer.phone || '—' }}</td>
                                <td class="pos-v3-customer-list__address">{{ customer.address || '—' }}</td>
                                <td>
                                    <div class="pos-v3-customer-list__actions">
                                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" title="Edit" @click="editCustomer(customer)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" title="View" @click="viewCustomer(customer)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm pos-v3-btn--danger" title="Delete" @click="deleteCustomer(customer)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="pos-v3-customer-modal__empty">
                        {{ isLoading ? 'Searching...' : 'No customers found.' }}
                    </div>
                </div>

                <div class="pos-v3-customer-modal__footer">
                    <button type="button" class="pos-v3-btn pos-v3-btn--ghost" @click="selectWalkIn">
                        Walk-in Customer
                    </button>
                    <div class="pos-v3-customer-modal__pager" v-if="lastPage > 1">
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" :disabled="page <= 1 || isLoading" @click="goPage(page - 1)">
                            Prev
                        </button>
                        <span class="pos-v3-customer-modal__page-label">Page {{ page }} / {{ lastPage }}</span>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" :disabled="page >= lastPage || isLoading" @click="goPage(page + 1)">
                            Next
                        </button>
                    </div>
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
            isLoading: function () {
                return this.uiStore.loading.customers;
            },
            customers: function () {
                return this.customerStore.customers;
            },
            page: function () {
                return this.customerStore.page;
            },
            lastPage: function () {
                return this.customerStore.lastPage;
            },
            selectedCustomer: function () {
                return this.customerStore.selectedCustomer;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            isSelected: function (customer) {
                return this.selectedCustomer && Number(this.selectedCustomer.id) === Number(customer.id);
            },
            selectCustomer: function (customer) {
                this.customerStore.selectCustomer(customer);
            },
            selectWalkIn: function () {
                this.customerStore.clearSelection();
            },
            goPage: function (page) {
                this.customerStore.searchCustomers(page);
            },
            editCustomer: function (customer) {
                this.customerStore.openEditCustomer(customer);
            },
            viewCustomer: function (customer) {
                this.customerStore.openViewCustomer(customer);
            },
            deleteCustomer: function (customer) {
                this.customerStore.deleteCustomer(customer);
            },
        },
    };
})(window);
