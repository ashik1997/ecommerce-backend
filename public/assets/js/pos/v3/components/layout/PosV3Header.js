(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3Header = {
        name: 'PosV3Header',
        template: `
            <header class="pos-v3-header pos-v3-card">
                <div class="pos-v3-header__strip">
                    <div class="pos-v3-header__menu">
                        <button type="button" class="pos-v3-icon-btn d-lg-none" @click="toggleMobileMenu" title="Menu">
                            <i class="fa fa-fw fa-bars"></i>
                        </button>
                        <button type="button" class="pos-v3-icon-btn d-none d-lg-inline-flex" @click="toggleDesktopMenu" title="Toggle sidebar">
                            <i class="fa fa-fw fa-bars"></i>
                        </button>
                    </div>
                    <pos-v3-target-stats></pos-v3-target-stats>
                    <pos-v3-clock></pos-v3-clock>
                </div>

                <div class="pos-v3-header__controls-row">
                    <div class="pos-v3-header__field pos-v3-header__field--inline">
                        <label class="pos-v3-header__inline-label" for="pos-v3-warehouse">Warehouse</label>
                        <select
                            id="pos-v3-warehouse"
                            class="pos-v3-select pos-v3-select--compact"
                            :value="warehouseSelectValue"
                            @change="onWarehouseChange">
                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                {{ warehouse.title }}
                            </option>
                        </select>
                    </div>

                    <div class="pos-v3-header__field pos-v3-header__field--inline">
                        <label class="pos-v3-header__inline-label" for="pos-v3-price-type">Pricing</label>
                        <select
                            id="pos-v3-price-type"
                            class="pos-v3-select pos-v3-select--compact"
                            :value="selectedProductPriceType"
                            @change="onPriceTypeChange">
                            <option value="retail_price">Retail Price</option>
                            <option value="wholesale_price">Wholesale Price</option>
                            <option value="product_price">Product Price</option>
                        </select>
                    </div>

                    <span class="pos-v3-header__mode" v-if="modeLabel">
                        {{ modeLabel }}
                        <span v-if="isLoadingContext" class="pos-v3-header__loading">…</span>
                    </span>

                    <pos-v3-customer-bar></pos-v3-customer-bar>
                </div>
            </header>
        `,
        computed: {
            warehouses: function () {
                return this.configStore.warehouses;
            },
            selectedWarehouseId: function () {
                return this.orderStore.selectedWarehouseId;
            },
            warehouseSelectValue: function () {
                return this.selectedWarehouseId == null ? '' : String(this.selectedWarehouseId);
            },
            selectedProductPriceType: function () {
                return this.orderStore.selectedProductPriceType;
            },
            modeLabel: function () {
                return this.orderStore.modeLabel;
            },
            configStore: function () {
                return global.PosV3.useConfigStore();
            },
            orderStore: function () {
                return global.PosV3.useOrderStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            isLoadingContext: function () {
                return this.uiStore.loading.orderLoad;
            },
        },
        methods: {
            onWarehouseChange: function (event) {
                const value = event.target.value;
                this.orderStore.setWarehouse(Number(value));
            },
            onPriceTypeChange: function (event) {
                this.orderStore.setPriceType(event.target.value);
            },
            toggleMobileMenu: function () {
                const btn = document.getElementById('vertical-menu-btn');

                if (btn) {
                    btn.click();
                }
            },
            toggleDesktopMenu: function () {
                document.body.classList.toggle('lg_hide_menu');
            },
        },
    };
})(window);
