(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function withWarehouseParams(params) {
        const payload = Object.assign({}, params || {});
        const orderStore = global.PosV3.useOrderStore && global.PosV3.useOrderStore();

        if (orderStore && orderStore.selectedWarehouseId) {
            payload.warehouse_id = orderStore.selectedWarehouseId;
        }

        return payload;
    }

    global.PosV3.api = {
        get: function get(url, params) {
            if (!global.axios) {
                return Promise.reject(new Error('axios is not available'));
            }

            return global.axios.get(url, { params: withWarehouseParams(params) });
        },
        post: function post(url, body) {
            if (!global.axios) {
                return Promise.reject(new Error('axios is not available'));
            }

            const payload = withWarehouseParams(body);

            return global.axios.post(url, payload);
        },
    };
})(window);
