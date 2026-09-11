(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3BarcodeSearch = {
        name: 'PosV3BarcodeSearch',
        template: `
            <div class="pos-v3-search-field pos-v3-search-field--barcode">
                <input
                    id="pos-v3-barcode-input"
                    type="text"
                    class="pos-v3-input"
                    :class="{ 'is-loading': isLoading }"
                    :value="barcodeQuery"
                    placeholder="Barcode scan (F2)"
                    title="Shortcut: F2"
                    @focus="onFocus"
                    @input="onInput">
            </div>
        `,
        computed: {
            barcodeQuery: function () {
                return this.searchStore.barcodeQuery;
            },
            isLoading: function () {
                return this.uiStore.loading.barcode;
            },
            searchStore: function () {
                return global.PosV3.useSearchStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
        },
        created: function () {
            this.debouncedLookup = global.PosV3.debounce(function () {
                this.searchStore.lookupBarcode();
            }.bind(this), 300);
        },
        methods: {
            onFocus: function () {
                this.searchStore.onBarcodeFocus();
            },
            onInput: function (event) {
                this.searchStore.setBarcodeQuery(event.target.value);
                this.debouncedLookup();
            },
        },
    };
})(window);
