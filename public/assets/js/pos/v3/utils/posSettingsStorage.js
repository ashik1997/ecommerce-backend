(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    var STORAGE_KEY = 'pos_settings';

    function getDefaults() {
        return {
            ui: {
                hideProductListAfterSelect: true,
            },
        };
    }

    function mergeSettings(defaults, saved) {
        saved = saved && typeof saved === 'object' ? saved : {};

        return {
            ui: Object.assign({}, defaults.ui, saved.ui || {}),
        };
    }

    global.PosV3.posSettingsStorage = {
        getDefaults: getDefaults,
        load: function load() {
            try {
                var raw = global.localStorage.getItem(STORAGE_KEY);

                if (!raw) {
                    return getDefaults();
                }

                return mergeSettings(getDefaults(), JSON.parse(raw));
            } catch (error) {
                return getDefaults();
            }
        },
        save: function save(settings) {
            try {
                global.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
            } catch (error) {
                // Ignore quota / private mode errors.
            }
        },
    };
})(window);
