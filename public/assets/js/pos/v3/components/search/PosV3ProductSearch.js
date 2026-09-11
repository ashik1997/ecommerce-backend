(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3ProductSearch = {
        name: 'PosV3ProductSearch',
        template: `
            <div class="pos-v3-search-field">
                <input
                    id="pos-v3-product-search-input"
                    type="text"
                    class="pos-v3-input"
                    :class="{ 'is-loading': isLoading }"
                    :value="query"
                    placeholder="Search product by name (F3)"
                    title="Shortcut: F3"
                    @focus="onFocus"
                    @input="onInput">
            </div>
        `,
        computed: {
            query: function () {
                return this.searchStore.query;
            },
            isLoading: function () {
                return this.uiStore.loading.search;
            },
            searchStore: function () {
                return global.PosV3.useSearchStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
        },
        created: function () {
            this.debouncedFetch = global.PosV3.debounce(function () {
                this.searchStore.fetchProducts();
            }.bind(this), 1000);
        },
        methods: {
            onFocus: function () {
                this.searchStore.onSearchFocus();
            },
            onInput: function (event) {
                this.searchStore.setQuery(event.target.value);
                this.debouncedFetch();
            },
        },
    };
})(window);
