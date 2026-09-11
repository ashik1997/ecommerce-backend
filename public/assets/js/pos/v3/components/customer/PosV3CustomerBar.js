(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CustomerBar = {
        name: 'PosV3CustomerBar',
        template: `
            <div class="pos-v3-customer-bar">
                <div class="pos-v3-customer-bar__avatar-wrap">
                    <img
                        v-if="customerImage"
                        :src="customerImage"
                        alt=""
                        class="pos-v3-customer-bar__avatar-img">
                </div>

                <div class="pos-v3-customer-bar__main">
                    <div v-if="!isNewCustomer" class="pos-v3-customer-bar__name">
                        {{ customerLabel }}
                    </div>
                    <div v-else class="pos-v3-customer-bar__name-input">
                        <input
                            type="text"
                            class="pos-v3-input pos-v3-input--compact"
                            :value="customerName"
                            @input="setCustomerName($event.target.value)"
                            @keydown.enter="saveNewCustomer"
                            placeholder="Enter name">
                    </div>

                    <div class="pos-v3-customer-bar__phone-row">
                        <input
                            type="text"
                            class="pos-v3-input pos-v3-input--compact"
                            :value="customerPhone"
                            maxlength="11"
                            placeholder="Enter phone"
                            @input="onPhoneInput($event.target.value)"
                            @keyup="validatePhoneOnType"
                            @keydown.enter="searchByPhone">
                        <small v-if="isInvalidPhone" class="pos-v3-customer-bar__error">Invalid phone number</small>
                    </div>
                </div>

                <div class="pos-v3-customer-bar__actions">
                    <button
                        v-if="isNewCustomer"
                        type="button"
                        class="pos-v3-icon-btn"
                        title="Save customer"
                        :disabled="isLoading"
                        @click="saveNewCustomer">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="button" class="pos-v3-icon-btn" title="Reset to walk-in" @click="resetCustomer">
                        <i class="fas fa-redo"></i>
                    </button>
                    <button type="button" class="pos-v3-icon-btn" title="Search customers" @click="openModal">
                        <i class="fas fa-search"></i>
                    </button>
                    <button
                        v-if="!isWalkIn && !isNewCustomer"
                        type="button"
                        class="pos-v3-icon-btn"
                        title="Edit customer"
                        @click="openEditModal">
                        <i class="fas fa-user-edit"></i>
                    </button>

                    <button 
                        v-if="showStats"
                        type="button" 
                        class=" pos-v3-icon-btn" :disabled="isHistoryLoading"
                        title="Customer History" 
                        @click="openHistory">
                            <i class="fas fa-history"></i>
                    </button>
                </div>

                <div v-if="showStats" class="pos-v3-customer-bar__stats">
                    
                    <span v-if="dueAmount > 0"><b>Due:</b> {{ formatNumber(dueAmount) }}</span>
                    <span v-if="availableAdvance > 0"><b>Bal:</b> {{ formatNumber(availableAdvance) }}</span>
                    <span v-if="orderCount > 0"><b>Total Orders:</b> {{ orderCount }}</span>
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
            selectedCustomer: function () {
                return this.customerStore.selectedCustomer;
            },
            isWalkIn: function () {
                return this.customerStore.isWalkIn;
            },
            isNewCustomer: function () {
                return this.customerStore.isNewCustomer;
            },
            isInvalidPhone: function () {
                return this.customerStore.isInvalidPhone;
            },
            isLoading: function () {
                return this.uiStore.loading.customers;
            },
            customerPhone: function () {
                return this.customerStore.customerPhone;
            },
            customerName: function () {
                return this.selectedCustomer && this.selectedCustomer.name
                    ? this.selectedCustomer.name
                    : '';
            },
            customerLabel: function () {
                if (this.isWalkIn) {
                    return '1 - walking customer';
                }

                const id = this.selectedCustomer && this.selectedCustomer.id;
                const name = this.selectedCustomer && this.selectedCustomer.name;

                return (id ? id + ' - ' : '') + (name || 'Customer');
            },
            customerImage: function () {
                return this.customerStore.imageUrl;
            },
            dueAmount: function () {
                return this.customerStore.dueAmount;
            },
            availableAdvance: function () {
                return this.customerStore.availableAdvance;
            },
            orderCount: function () {
                return this.customerStore.orderCount;
            },
            isHistoryLoading: function () {
                return this.uiStore.loading.customerHistory;
            },
            showStats: function () {
                return !this.isWalkIn && !this.isNewCustomer;
            },
        },
        methods: {
            formatNumber: function (value) {
                return global.Intl.NumberFormat('en-US').format(Number(value) || 0);
            },
            onPhoneInput: function (value) {
                this.customerStore.setCustomerPhone(value);
            },
            validatePhoneOnType: function () {
                this.customerStore.validatePhoneOnType();
            },
            searchByPhone: function () {
                this.customerStore.searchCustomerByPhone();
            },
            saveNewCustomer: function () {
                this.customerStore.saveNewCustomer();
            },
            resetCustomer: function () {
                this.customerStore.resetToWalkingCustomer();
            },
            setCustomerName: function (value) {
                if (this.selectedCustomer) {
                    this.selectedCustomer.name = value || '';
                }
            },
            openModal: function () {
                this.customerStore.setModalMode('list');
                this.uiStore.setCustomerModalOpen(true);
            },
            openEditModal: function () {
                this.customerStore.openEditSelectedCustomer();
            },
            openHistory: function () {
                this.customerStore.openCustomerHistory();
            },
        },
    };
})(window);
