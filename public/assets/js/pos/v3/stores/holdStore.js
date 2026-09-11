(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useHoldStore = Pinia.defineStore('posV3Hold', {
        state: function () {
            return {
                holds: [],
            };
        },
        actions: {
            holdCurrentOrder: function () {
                const configStore = global.PosV3.useConfigStore();
                const cartStore = global.PosV3.useCartStore();
                const uiStore = global.PosV3.useUiStore();
                const orderStore = global.PosV3.useOrderStore();
                const draftStore = global.PosV3.useDraftStore();
                const route = configStore.routes.hold;

                if (!configStore.features.hold || !route) {
                    return Promise.resolve();
                }

                if (!cartStore.items.length) {
                    global.PosV3.alerts.show('Cart is empty', 'warning');
                    return Promise.resolve();
                }

                uiStore.setLoading('hold', true);

                return global.PosV3.api.post(route, global.PosV3.orderStateSerializer.buildHoldPayload())
                    .then(function (response) {
                        if (!response.data || !response.data.success) {
                            global.PosV3.alerts.show(
                                (response.data && response.data.message) || 'Hold failed',
                                'error'
                            );
                            return;
                        }

                        const holdId = response.data.data && response.data.data.id;
                        global.PosV3.alerts.show(
                            'Order held.' + (holdId ? ' ID: ' + holdId : ''),
                            'success'
                        );
                        orderStore.clearAfterSubmit();
                        draftStore.clearDraft();
                    })
                    .catch(function () {
                        global.PosV3.alerts.show('Hold failed', 'error');
                    })
                    .finally(function () {
                        uiStore.setLoading('hold', false);
                    });
            },
            fetchHolds: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.holds;

                if (!route) {
                    return Promise.resolve();
                }

                uiStore.setLoading('holdList', true);

                return global.PosV3.api.get(route)
                    .then(function (response) {
                        this.holds = response.data && response.data.success
                            ? (response.data.data || [])
                            : [];
                    }.bind(this))
                    .catch(function () {
                        this.holds = [];
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('holdList', false);
                    });
            },
            restoreHold: function (holdId) {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const orderStore = global.PosV3.useOrderStore();
                const routeTemplate = configStore.routes.getHold;

                if (!routeTemplate || !holdId) {
                    return Promise.resolve();
                }

                const url = routeTemplate.replace('__ID__', String(holdId));

                uiStore.setLoading('hold', true);

                return global.PosV3.api.get(url)
                    .then(function (response) {
                        if (!response.data || !response.data.success) {
                            global.PosV3.alerts.show('Failed to load held order', 'error');
                            return;
                        }

                        const data = response.data.data || {};

                        global.PosV3.orderStateSerializer.apply({
                            cart: data.cart || [],
                            totals: data.totals || {},
                            customer: data.customer || null,
                            order_note: data.note || (data.hold && data.hold.meta ? data.hold.meta.note : '') || '',
                        });

                        orderStore.holdId = holdId;
                        uiStore.setHoldModalOpen(false);
                        global.PosV3.useDraftStore().clearDraft();
                    })
                    .catch(function () {
                        global.PosV3.alerts.show('Failed to load held order', 'error');
                    })
                    .finally(function () {
                        uiStore.setLoading('hold', false);
                    });
            },
            openHoldList: function () {
                const uiStore = global.PosV3.useUiStore();

                uiStore.setHoldModalOpen(true);
                return this.fetchHolds();
            },
        },
    });
})(window);
