(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useUiStore = Pinia.defineStore('posV3Ui', {
        state: function () {
            return {
                searchOpen: false,
                categorySidebarOpen: false,
                settingsModalOpen: false,
                customerModalOpen: false,
                customerHistoryModalOpen: false,
                holdModalOpen: false,
                loading: {
                    bootstrap: false,
                    search: false,
                    barcode: false,
                    cart: false,
                    totals: false,
                    coupon: false,
                    customers: false,
                    customerDetail: false,
                    customerHistory: false,
                    customerSave: false,
                    payments: false,
                    submit: false,
                    hold: false,
                    holdList: false,
                    orderLoad: false,
                    targetStats: false,
                    categories: false,
                    deliveryOptions: false,
                },
            };
        },
        actions: {
            setSearchOpen: function (value) {
                this.searchOpen = !!value;
            },
            setCategorySidebarOpen: function (value) {
                this.categorySidebarOpen = !!value;
            },
            toggleCategorySidebar: function () {
                this.categorySidebarOpen = !this.categorySidebarOpen;
            },
            setSettingsModalOpen: function (value) {
                this.settingsModalOpen = !!value;
            },
            toggleSettingsModal: function () {
                this.settingsModalOpen = !this.settingsModalOpen;
            },
            setCustomerModalOpen: function (value) {
                this.customerModalOpen = !!value;
            },
            setCustomerHistoryModalOpen: function (value) {
                this.customerHistoryModalOpen = !!value;
            },
            setHoldModalOpen: function (value) {
                this.holdModalOpen = !!value;
            },
            setLoading: function (key, value) {
                if (!Object.prototype.hasOwnProperty.call(this.loading, key)) {
                    return;
                }

                this.loading[key] = !!value;
            },
        },
    });
})(window);
