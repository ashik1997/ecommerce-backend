(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CustomerHistoryModal = {
        name: 'PosV3CustomerHistoryModal',
        template: `
            <div v-if="isOpen" class="pos-v3-customer-history-modal" @click.self="close">
                <div class="pos-v3-customer-history-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pos-v3-customer-history-title">
                    <div class="pos-v3-customer-history-modal__header">
                        <div>
                            <h2 id="pos-v3-customer-history-title" class="pos-v3-customer-history-modal__title">Customer History</h2>
                            <div class="pos-v3-customer-history-modal__subtitle">{{ customerLabel }}</div>
                        </div>
                        <button type="button" class="pos-v3-customer-history-modal__close" @click="close" aria-label="Close">&times;</button>
                    </div>

                    <div class="pos-v3-customer-history-modal__body" :class="{ 'is-loading': isLoading }">
                        <div v-if="isLoading && !history" class="pos-v3-customer-history-modal__empty">
                            Loading customer history...
                        </div>

                        <template v-else-if="history">
                            <div class="pos-v3-customer-history-summary">
                                <div class="pos-v3-customer-history-summary__item">
                                    <span>Total Orders</span>
                                    <strong>{{ summary.total_orders || 0 }}</strong>
                                </div>
                                <div class="pos-v3-customer-history-summary__item">
                                    <span>Purchase Amount</span>
                                    <strong>{{ formatMoney(summary.total_purchase_amount || 0) }}</strong>
                                </div>
                                <div class="pos-v3-customer-history-summary__item">
                                    <span>Total Returns</span>
                                    <strong>{{ summary.total_returns || 0 }}</strong>
                                </div>
                                <div class="pos-v3-customer-history-summary__item">
                                    <span>Total Refunds</span>
                                    <strong>{{ formatMoney(summary.total_refund_amount || 0) }}</strong>
                                </div>
                            </div>

                            <div class="pos-v3-customer-history-tabs" role="tablist" aria-label="Customer history sections">
                                <button type="button" :class="tabClass('orders')" @click="setTab('orders')">Orders</button>
                                <button type="button" :class="tabClass('returns')" @click="setTab('returns')">Returns</button>
                                <button type="button" :class="tabClass('refunds')" @click="setTab('refunds')">Refunds</button>
                            </div>
                            
                            

                            <div v-if="activeTab === 'orders'" class="pos-v3-customer-history-panel">
                                <template v-if="orders.length">
                                    <table class="pos-v3-customer-history-table">
                                        <thead>
                                            <tr>
                                                <th>Order Reference</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Paid</th>
                                                <th>Due</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="order in orders" :key="'order-' + order.id">
                                                <td>{{ order.order_code || ('#' + order.id) }}</td>
                                                <td>{{ formatDate(order.sale_date) }}</td>
                                                <td>{{ formatMoney(order.total || 0) }}</td>
                                                <td>{{ formatMoney(order.paid_amount || 0) }}</td>
                                                <td>{{ formatMoney(order.due_amount || 0) }}</td>
                                                <td>
                                                <a v-if="order.slug" :href="'print/' + order.slug" 
                                                    target="_blank" class="btn btn-action btn-action--view pos-v3-icon-btn"
                                                    title="Pos Invoice / Print">
                                                    <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-if="ordersPagination.last_page > 1" class="pos-v3-customer-history-pager">
                                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" :disabled="isLoading || ordersPagination.current_page <= 1" @click="loadOrdersPage(ordersPagination.current_page - 1)">Prev</button>
                                        <span>Page {{ ordersPagination.current_page }} / {{ ordersPagination.last_page }}</span>
                                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" :disabled="isLoading || ordersPagination.current_page >= ordersPagination.last_page" @click="loadOrdersPage(ordersPagination.current_page + 1)">Next</button>
                                    </div>
                                </template>
                                <div v-else class="pos-v3-customer-history-modal__empty">No order history found.</div>
                            </div>

                            <div v-else-if="activeTab === 'returns'" class="pos-v3-customer-history-panel">
                                <table v-if="returns.length" class="pos-v3-customer-history-table">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Return Qty</th>
                                            <th>Return Amount</th>
                                            <th>Return Date</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in returns" :key="'return-' + item.product_id + '-' + item.return_code">
                                            <td>{{ item.product_name || 'N/A' }}</td>
                                            <td>{{ formatNumber(item.return_qty || 0) }}</td>
                                            <td>{{ formatMoney(item.return_amount || 0) }}</td>
                                            <td>{{ formatDate(item.return_date) }}</td>
                                            <td>{{ item.return_code || item.order_code || 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div v-else class="pos-v3-customer-history-modal__empty">No return history found.</div>
                            </div>

                            <div v-else class="pos-v3-customer-history-panel">
                                <template v-if="refunds.length">
                                    <table class="pos-v3-customer-history-table">
                                        <thead>
                                            <tr>
                                                <th>Refund Reference</th>
                                                <th>Refund Amount</th>
                                                <th>Date</th>
                                                <th>Related Order</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="refund in refunds" :key="'refund-' + refund.id">
                                                <td>{{ refund.refund_code || ('#' + refund.id) }}</td>
                                                <td>{{ formatMoney(refund.refund_amount || 0) }}</td>
                                                <td>{{ formatDate(refund.refund_date) }}</td>
                                                <td>{{ refund.order_code || refund.return_code || 'N/A' }}</td>
                                                <td>{{ refund.refund_status || 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-if="refundsPagination.last_page > 1" class="pos-v3-customer-history-pager">
                                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" :disabled="isLoading || refundsPagination.current_page <= 1" @click="loadRefundsPage(refundsPagination.current_page - 1)">Prev</button>
                                        <span>Page {{ refundsPagination.current_page }} / {{ refundsPagination.last_page }}</span>
                                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm" :disabled="isLoading || refundsPagination.current_page >= refundsPagination.last_page" @click="loadRefundsPage(refundsPagination.current_page + 1)">Next</button>
                                    </div>
                                </template>
                                <div v-else class="pos-v3-customer-history-modal__empty">No refund history found.</div>
                            </div>
                        </template>

                        <div v-else class="pos-v3-customer-history-modal__empty">
                            No customer history found.
                        </div>
                    </div>

                    <div class="pos-v3-customer-history-modal__footer">
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost" :disabled="isLoading" @click="refresh">Refresh</button>
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
            isOpen: function () {
                return this.uiStore.customerHistoryModalOpen;
            },
            isLoading: function () {
                return this.uiStore.loading.customerHistory;
            },
            history: function () {
                return this.customerStore.history;
            },
            summary: function () {
                return this.history && this.history.summary ? this.history.summary : {};
            },
            activeTab: function () {
                return this.customerStore.historyActiveTab || 'orders';
            },
            purchases: function () {
                return this.history && this.history.purchases ? this.history.purchases : [];
            },
            services: function () {
                return this.history && this.history.services ? this.history.services : [];
            },
            orders: function () {
                return this.history && this.history.orders ? this.history.orders : [];
            },
            ordersPagination: function () {
                return this.history && this.history.orders_pagination
                    ? this.history.orders_pagination
                    : { current_page: 1, last_page: 1, total: 0 };
            },
            returns: function () {
                return this.history && this.history.returns ? this.history.returns : [];
            },
            refunds: function () {
                return this.history && this.history.refunds ? this.history.refunds : [];
            },
            refundsPagination: function () {
                return this.history && this.history.refunds_pagination
                    ? this.history.refunds_pagination
                    : { current_page: 1, last_page: 1, total: 0 };
            },
            customerLabel: function () {
                const customer = this.history && this.history.customer
                    ? this.history.customer
                    : this.customerStore.selectedCustomer;

                if (!customer) {
                    return '';
                }

                return (customer.name || 'Customer') + (customer.phone ? ' - ' + customer.phone : '');
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            formatNumber: function (value) {
                return global.Intl.NumberFormat('en-US').format(Number(value) || 0);
            },
            formatDate: function (value) {
                return value || 'N/A';
            },
            tabClass: function (tab) {
                return {
                    'pos-v3-customer-history-tabs__button': true,
                    'is-active': this.activeTab === tab,
                };
            },
            setTab: function (tab) {
                this.customerStore.setHistoryActiveTab(tab);
            },
            close: function () {
                this.customerStore.closeCustomerHistory();
            },
            refresh: function () {
                const customer = this.customerStore.selectedCustomer;

                if (!customer || !customer.id) {
                    return;
                }

                this.customerStore.history = null;
                this.customerStore.historyCustomer = null;
                this.customerStore.fetchCustomerHistory(customer.id, {});
            },
            loadOrdersPage: function (page) {
                const customer = this.customerStore.selectedCustomer;

                if (!customer || !customer.id || page < 1) {
                    return;
                }

                this.customerStore.fetchCustomerHistory(customer.id, {
                    orders_page: page,
                    refunds_page: this.refundsPagination.current_page || 1,
                });
            },
            loadRefundsPage: function (page) {
                const customer = this.customerStore.selectedCustomer;

                if (!customer || !customer.id || page < 1) {
                    return;
                }

                this.customerStore.fetchCustomerHistory(customer.id, {
                    orders_page: this.ordersPagination.current_page || 1,
                    refunds_page: page,
                });
            },
        },
    };
})(window);


// <button type="button" :class="tabClass('purchases')" @click="setTab('purchases')">Purchases</button>

// <div v-if="activeTab === 'purchases'" class="pos-v3-customer-history-panel">
//    <table v-if="purchases.length" class="pos-v3-customer-history-table">
//        <thead>
//            <tr>
//                <th>Product Name</th>
//                <th>Qty Purchased</th>
//                <th>Purchase Count</th>
//                <th>Total Amount</th>
//                <th>Last Purchase</th>
//            </tr>
//        </thead>
//        <tbody>
//            <tr v-for="item in purchases" :key="'purchase-' + item.product_id + '-' + item.product_name">
//                <td>{{ item.product_name || 'N/A' }}</td>
//                <td>{{ formatNumber(item.qty_purchased || 0) }}</td>
//                <td>{{ item.purchase_count || 0 }}</td>
//                <td>{{ formatMoney(item.total_amount || 0) }}</td>
//                <td>{{ formatDate(item.last_purchase_date) }}</td>
//            </tr>
//        </tbody>
//    </table>
//    <div v-else class="pos-v3-customer-history-modal__empty">No purchase history found.</div>
//</div>