(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3App = {
        name: 'PosV3App',
        template: `
            <div class="pos-v3-app" v-cloak>
                <pos-v3-header></pos-v3-header>
                <div class="pos-v3-main">
                    <pos-v3-left-panel></pos-v3-left-panel>
                    <pos-v3-right-panel></pos-v3-right-panel>
                </div>
                <pos-v3-customer-modal></pos-v3-customer-modal>
                <pos-v3-customer-history-modal></pos-v3-customer-history-modal>
                <pos-v3-hold-list-modal></pos-v3-hold-list-modal>
                <pos-v3-settings-modal></pos-v3-settings-modal>
            </div>
        `,
        mounted: function () {
            global.PosV3.keyboardShortcuts.init();

            this.$nextTick(function () {
                const barcodeInput = document.getElementById('pos-v3-barcode-input');

                if (barcodeInput && typeof barcodeInput.focus === 'function') {
                    barcodeInput.focus();
                }
            });
        },
        beforeUnmount: function () {
            global.PosV3.keyboardShortcuts.destroy();
        },
    };
})(window);
