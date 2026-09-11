(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3LeftPanel = {
        name: 'PosV3LeftPanel',
        template: `
            <section class="pos-v3-left">
                <button
                    type="button"
                    class="pos-v3-category-toggle"
                    :class="{ 'is-open': categorySidebarOpen }"
                    :title="categorySidebarOpen ? 'Hide categories' : 'Browse categories'"
                    @click="toggleCategorySidebar">
                    <i class="fas fa-folder"></i>
                </button>

                <button
                    type="button"
                    class="pos-v3-settings-toggle"
                    :class="{ 'is-open': settingsModalOpen }"
                    title="POS settings"
                    @click="toggleSettingsModal">
                    <i class="fas fa-cog"></i>
                </button>

                <div
                    class="pos-v3-category-drawer"
                    :class="{ 'is-open': categorySidebarOpen }">
                    <button
                        type="button"
                        class="pos-v3-category-drawer__backdrop"
                        aria-label="Close categories"
                        @click="closeCategorySidebar">
                    </button>
                    <pos-v3-category-sidebar @close="closeCategorySidebar"></pos-v3-category-sidebar>
                </div>

                <div class="pos-v3-card pos-v3-left__main">
                    <div class="pos-v3-card__body pos-v3-left__body">
                        <div class="pos-v3-search-row">
                            <pos-v3-product-search></pos-v3-product-search>
                            <pos-v3-barcode-search></pos-v3-barcode-search>
                        </div>

                        <pos-v3-cart-table></pos-v3-cart-table>

                        <pos-v3-product-results v-if="searchOpen"></pos-v3-product-results>
                    </div>
                </div>
            </section>
        `,
        computed: {
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            searchOpen: function () {
                return this.uiStore.searchOpen;
            },
            categorySidebarOpen: function () {
                return this.uiStore.categorySidebarOpen;
            },
            settingsModalOpen: function () {
                return this.uiStore.settingsModalOpen;
            },
        },
        methods: {
            toggleCategorySidebar: function () {
                this.uiStore.toggleCategorySidebar();
            },
            closeCategorySidebar: function () {
                this.uiStore.setCategorySidebarOpen(false);
            },
            toggleSettingsModal: function () {
                this.uiStore.toggleSettingsModal();
            },
        },
    };
})(window);
