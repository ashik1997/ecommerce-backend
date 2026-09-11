(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.registerComponents = function registerComponents(app) {
        const components = global.PosV3.components || {};

        Object.keys(components).forEach(function (name) {
            app.component(name, components[name]);
        });
    };

    global.PosV3.bootstrapStores = function bootstrapStores() {
        const configStore = global.PosV3.useConfigStore();
        const orderStore = global.PosV3.useOrderStore();
        const customerStore = global.PosV3.useCustomerStore();
        const paymentStore = global.PosV3.usePaymentStore();

        configStore.initFromWindow();
        global.PosV3.useSettingsStore().initFromStorage();
        orderStore.initFromConfig();

        return Promise.all([
            customerStore.loadDefaultCustomer(),
            paymentStore.loadMethods(),
            global.PosV3.useTargetStatsStore().load(),
            global.PosV3.useCategoryStore().loadCategories(),
            global.PosV3.useDeliveryOptionsStore().loadAll(),
        ]).then(function () {
            global.PosV3.useDraftStore().initAutoSave();
            return global.PosV3.useOrderStore().initFromUrlContext();
        }).then(function (loadedFromContext) {
            if (loadedFromContext) {
                return true;
            }

            return global.PosV3.useDraftStore().tryAutoRestore();
        });
    };

    global.PosV3.afterMount = function afterMount() {
        global.PosV3.useDraftStore().refreshDraftFlag();
    };
})(window);
