(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useConfigStore = Pinia.defineStore('posV3Config', {
        state: function () {
            return {
                configVersion: 1,
                routes: {},
                warehouses: [],
                salesUsers: [],
                affiliates: [],
                imageUrl: '',
                features: {},
                defaults: {
                    customerId: 1,
                    warehouseId: null,
                    priceType: 'product_price',
                },
                edit: null,
                quotation: null,
                initialized: false,
            };
        },
        getters: {
            isReady: function (state) {
                return state.initialized;
            },
        },
        actions: {
            initFromWindow: function () {
                const config = global.POS_V3_CONFIG || {};

                this.configVersion = config.configVersion || 1;
                this.routes = config.routes || {};
                this.warehouses = Array.isArray(config.warehouses) ? config.warehouses : [];
                this.salesUsers = Array.isArray(config.sales_users) ? config.sales_users : [];
                this.affiliates = Array.isArray(config.affiliates) ? config.affiliates : [];
                this.imageUrl = config.image_url || '';
                this.features = config.features || {};
                this.defaults = Object.assign({}, this.defaults, config.defaults || {});
                this.edit = config.edit || null;
                this.quotation = config.quotation || null;
                this.initialized = true;
            },
        },
    });
})(window);
