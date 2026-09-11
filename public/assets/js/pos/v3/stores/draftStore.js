(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useDraftStore = Pinia.defineStore('posV3Draft', {
        state: function () {
            return {
                hasSavedDraft: false,
                autoSaveReady: false,
            };
        },
        actions: {
            refreshDraftFlag: function () {
                const data = global.PosV3.draftStorage.read();
                this.hasSavedDraft = !!(data && Array.isArray(data.cart) && data.cart.length);
            },
            saveDraft: function () {
                const cartStore = global.PosV3.useCartStore();

                if (!cartStore.items.length) {
                    global.PosV3.alerts.show('Cart is empty', 'warning');
                    return false;
                }

                const payload = global.PosV3.orderStateSerializer.serialize();
                const saved = global.PosV3.draftStorage.write(payload);

                if (!saved) {
                    global.PosV3.alerts.show('Failed to save order locally.', 'error');
                    return false;
                }

                this.hasSavedDraft = true;
                global.PosV3.alerts.show('Order saved locally. You can restore it later in this tab.', 'success');
                return true;
            },
            restoreDraft: function () {
                const data = global.PosV3.draftStorage.read();

                if (!data || !Array.isArray(data.cart) || !data.cart.length) {
                    global.PosV3.alerts.show('No saved order found.', 'info');
                    this.hasSavedDraft = false;
                    return false;
                }

                global.PosV3.orderStateSerializer.apply(data);
                this.hasSavedDraft = false;
                global.PosV3.draftStorage.clear();
                global.PosV3.alerts.show('Saved order restored.', 'success');
                return true;
            },
            clearDraft: function () {
                global.PosV3.draftStorage.clear();
                this.hasSavedDraft = false;
            },
            persistAutoSave: function () {
                const cartStore = global.PosV3.useCartStore();

                if (!cartStore.items.length) {
                    this.clearDraft();
                    return;
                }

                const payload = global.PosV3.orderStateSerializer.serialize();
                global.PosV3.draftStorage.write(payload);
                this.hasSavedDraft = true;
            },
            initAutoSave: function () {
                if (this.autoSaveReady) {
                    return;
                }

                this.autoSaveReady = true;
                this.refreshDraftFlag();

                const debouncedSave = global.PosV3.debounce(function () {
                    global.PosV3.useDraftStore().persistAutoSave();
                }, 400);

                global.PosV3.useCartStore().$subscribe(function () {
                    debouncedSave();
                });
                global.PosV3.useCustomerStore().$subscribe(function () {
                    debouncedSave();
                });
                global.PosV3.useTotalsStore().$subscribe(function () {
                    debouncedSave();
                });
                global.PosV3.useOrderStore().$subscribe(function () {
                    debouncedSave();
                });
            },
            tryAutoRestore: function () {
                const cartStore = global.PosV3.useCartStore();
                const data = global.PosV3.draftStorage.read();

                this.refreshDraftFlag();

                if (cartStore.items.length || !data || !Array.isArray(data.cart) || !data.cart.length) {
                    return Promise.resolve(false);
                }

                return global.PosV3.alerts.confirm(
                    'Restore saved order?',
                    'A locally saved POS order was found for this tab. Restore it now?'
                ).then(function (shouldRestore) {
                    if (!shouldRestore) {
                        return false;
                    }

                    global.PosV3.orderStateSerializer.apply(data);
                    global.PosV3.useDraftStore().clearDraft();
                    return true;
                });
            },
        },
    });
})(window);
