(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function getDefaultUiSettings() {
        if (global.PosV3.posSettingsStorage && typeof global.PosV3.posSettingsStorage.getDefaults === 'function') {
            return global.PosV3.posSettingsStorage.getDefaults().ui;
        }

        return {
            hideProductListAfterSelect: true,
        };
    }

    global.PosV3.useSettingsStore = Pinia.defineStore('posV3Settings', {
        state: function () {
            return {
                loaded: false,
                ui: getDefaultUiSettings(),
            };
        },
        getters: {
            shouldHideProductListAfterSelect: function (state) {
                return state.ui.hideProductListAfterSelect !== false;
            },
        },
        actions: {
            initFromStorage: function () {
                var saved = global.PosV3.posSettingsStorage
                    ? global.PosV3.posSettingsStorage.load()
                    : { ui: getDefaultUiSettings() };

                this.ui = Object.assign({}, saved.ui);
                this.loaded = true;
            },
            persist: function () {
                if (!global.PosV3.posSettingsStorage) {
                    return;
                }

                global.PosV3.posSettingsStorage.save({
                    ui: Object.assign({}, this.ui),
                });
            },
            setHideProductListAfterSelect: function (value) {
                this.ui.hideProductListAfterSelect = !!value;
                this.persist();
            },
        },
    });
})(window);
