(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function isEditableTarget(target) {
        if (!target) {
            return false;
        }

        const tag = String(target.tagName || '').toLowerCase();

        return tag === 'input'
            || tag === 'textarea'
            || tag === 'select'
            || target.isContentEditable;
    }

    function focusSelector(selector) {
        const element = document.querySelector(selector);

        if (element && typeof element.focus === 'function') {
            element.focus();

            if (typeof element.select === 'function') {
                element.select();
            }

            return true;
        }

        return false;
    }

    global.PosV3.keyboardShortcuts = {
        init: function init() {
            if (this._ready) {
                return;
            }

            this._ready = true;
            this._handler = this.handleKeydown.bind(this);
            document.addEventListener('keydown', this._handler, true);
        },
        destroy: function destroy() {
            if (!this._ready || !this._handler) {
                return;
            }

            document.removeEventListener('keydown', this._handler, true);
            this._ready = false;
            this._handler = null;
        },
        handleKeydown: function handleKeydown(event) {
            if (event.defaultPrevented) {
                return;
            }

            const key = event.key;

            if (key === 'F2') {
                event.preventDefault();
                focusSelector('#pos-v3-barcode-input');
                return;
            }

            if (key === 'F3') {
                event.preventDefault();
                focusSelector('#pos-v3-product-search-input');
                return;
            }

            if (key === 'Escape') {
                const uiStore = global.PosV3.useUiStore();

                if (uiStore.customerModalOpen) {
                    event.preventDefault();
                    global.PosV3.useCustomerStore().closeCustomerModal();
                    return;
                }

                if (uiStore.holdModalOpen) {
                    event.preventDefault();
                    uiStore.setHoldModalOpen(false);
                    return;
                }

                if (uiStore.categorySidebarOpen) {
                    event.preventDefault();
                    uiStore.setCategorySidebarOpen(false);
                    return;
                }

                if (uiStore.settingsModalOpen) {
                    event.preventDefault();
                    uiStore.setSettingsModalOpen(false);
                    return;
                }

                if (uiStore.searchOpen) {
                    event.preventDefault();
                    global.PosV3.useSearchStore().closeProductResults();
                    return;
                }
            }

            if (key === 'Enter' && (event.ctrlKey || event.metaKey) && !isEditableTarget(event.target)) {
                event.preventDefault();
                global.PosV3.useOrderStore().submitOrder();
            }
        },
    };
})(window);
