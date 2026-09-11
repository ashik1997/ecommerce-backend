(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useDeliveryOptionsStore = Pinia.defineStore('posV3DeliveryOptions', {
        state: function () {
            return {
                deliveryMethods: [],
                outlets: [],
                courierMethods: [],
                customerSources: [],
            };
        },
        actions: {
            loadAll: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const routes = configStore.routes || {};
                const requests = [];

                if (routes.deliveryMethods) {
                    requests.push(global.PosV3.api.get(routes.deliveryMethods).then(function (response) {
                        this.deliveryMethods = response.data && response.data.data ? response.data.data : [];
                    }.bind(this)));
                }

                if (routes.outlets) {
                    requests.push(global.PosV3.api.get(routes.outlets).then(function (response) {
                        this.outlets = response.data && response.data.data ? response.data.data : [];
                    }.bind(this)));
                }

                if (routes.courierMethods) {
                    requests.push(global.PosV3.api.get(routes.courierMethods).then(function (response) {
                        this.courierMethods = response.data && response.data.data ? response.data.data : [];
                    }.bind(this)));
                }

                if (routes.customerSource) {
                    requests.push(global.PosV3.api.get(routes.customerSource).then(function (response) {
                        this.customerSources = response.data && response.data.data ? response.data.data : [];
                    }.bind(this)));
                }

                if (!requests.length) {
                    return Promise.resolve();
                }

                uiStore.setLoading('deliveryOptions', true);

                return Promise.all(requests)
                    .catch(function () {
                        // Keep partial data on failure.
                    })
                    .finally(function () {
                        uiStore.setLoading('deliveryOptions', false);
                    });
            },
        },
    });
})(window);
