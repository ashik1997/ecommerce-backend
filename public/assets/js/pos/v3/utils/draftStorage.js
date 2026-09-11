(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.draftStorage = {
        getTabId: function getTabId() {
            if (!global.sessionStorage) {
                return 'default';
            }

            if (!global.sessionStorage.getItem('pos_v3_tab_id')) {
                global.sessionStorage.setItem(
                    'pos_v3_tab_id',
                    'tab_' + Date.now() + '_' + Math.random().toString(36).slice(2)
                );
            }

            return global.sessionStorage.getItem('pos_v3_tab_id');
        },
        getKey: function getKey() {
            return 'pos_v3_draft_' + this.getTabId();
        },
        read: function read() {
            if (!global.localStorage) {
                return null;
            }

            try {
                const raw = global.localStorage.getItem(this.getKey());

                if (!raw) {
                    return null;
                }

                return JSON.parse(raw);
            } catch (error) {
                console.warn('[POS v3] Draft read failed', error);
                return null;
            }
        },
        write: function write(data) {
            if (!global.localStorage) {
                return false;
            }

            try {
                global.localStorage.setItem(this.getKey(), JSON.stringify(data));
                return true;
            } catch (error) {
                console.warn('[POS v3] Draft write failed', error);
                return false;
            }
        },
        clear: function clear() {
            if (!global.localStorage) {
                return;
            }

            try {
                global.localStorage.removeItem(this.getKey());
            } catch (error) {
                console.warn('[POS v3] Draft clear failed', error);
            }
        },
    };
})(window);
