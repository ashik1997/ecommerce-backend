(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CustomerModal = {
        name: 'PosV3CustomerModal',
        template: `
            <div v-if="isOpen" class="pos-v3-customer-modal" @click.self="close">
                <div class="pos-v3-customer-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pos-v3-customer-modal-title">
                    <div class="pos-v3-customer-modal__header">
                        <h2 id="pos-v3-customer-modal-title" class="pos-v3-customer-modal__title">{{ modalTitle }}</h2>
                        <div class="pos-v3-customer-modal__header-actions">
                            <button
                                v-if="modalMode === 'list'"
                                type="button"
                                class="pos-v3-btn pos-v3-btn--accent pos-v3-btn--sm"
                                @click="openAdd">
                                <i class="fas fa-user-plus"></i> Add Customer
                            </button>
                            <button
                                v-if="modalMode === 'add' || modalMode === 'edit'"
                                type="button"
                                class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm"
                                @click="backToList">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button
                                v-if="modalMode === 'view'"
                                type="button"
                                class="pos-v3-btn pos-v3-btn--ghost pos-v3-btn--sm"
                                @click="backToList">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="pos-v3-customer-modal__close" @click="close" aria-label="Close">&times;</button>
                        </div>
                    </div>

                    <div v-if="modalMode === 'list'" class="pos-v3-customer-modal__search">
                        <input
                            type="text"
                            class="pos-v3-input"
                            placeholder="Search by name, phone, email or ID"
                            :value="searchQuery"
                            @input="onSearchInput"
                            ref="searchInput">
                    </div>

                    <div class="pos-v3-customer-modal__content">
                        <pos-v3-customer-list v-if="modalMode === 'list'"></pos-v3-customer-list>
                        <pos-v3-customer-form v-else-if="modalMode === 'add' || modalMode === 'edit'"></pos-v3-customer-form>
                        <pos-v3-customer-view v-else-if="modalMode === 'view'"></pos-v3-customer-view>
                    </div>
                </div>
            </div>
        `,
        data: function () {
            return {
                debouncedSearch: null,
            };
        },
        computed: {
            customerStore: function () {
                return global.PosV3.useCustomerStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            isOpen: function () {
                return this.uiStore.customerModalOpen;
            },
            modalMode: function () {
                return this.customerStore.modalMode;
            },
            modalTitle: function () {
                return this.customerStore.modalTitle;
            },
            searchQuery: function () {
                return this.customerStore.searchQuery;
            },
        },
        watch: {
            isOpen: function (open) {
                if (!open) {
                    return;
                }

                this.customerStore.loadDistricts();

                if (this.modalMode === 'list') {
                    this.customerStore.searchCustomers(1);

                    this.$nextTick(function () {
                        const input = this.$refs.searchInput;

                        if (input && typeof input.focus === 'function') {
                            input.focus();
                        }
                    }.bind(this));
                }
            },
        },
        created: function () {
            this.debouncedSearch = global.PosV3.debounce(function () {
                this.customerStore.searchCustomers(1);
            }.bind(this), 500);
        },
        methods: {
            close: function () {
                this.customerStore.closeCustomerModal();
            },
            backToList: function () {
                this.customerStore.setModalMode('list');
            },
            openAdd: function () {
                this.customerStore.openAddCustomer();
            },
            onSearchInput: function (event) {
                this.customerStore.setSearchQuery(event.target.value);
                this.debouncedSearch();
            },
        },
    };
})(window);
