(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useSearchStore = Pinia.defineStore('posV3Search', {
        state: function () {
            return {
                query: '',
                barcodeQuery: '',
                products: [],
                hasMore: false,
                page: 1,
                perPage: 24,
                lastSelected: null,
                browseMode: 'search',
                browseType: null,
                browseId: null,
                browseLabel: '',
                expandedProductId: null,
            };
        },
        actions: {
            setExpandedProduct: function (productId) {
                this.expandedProductId = productId || null;
            },
            setPerPage: function (value) {
                const next = parseInt(value, 10);

                if (!Number.isFinite(next) || next <= 0) {
                    return Promise.resolve();
                }

                this.perPage = next;
                this.page = 1;

                if (this.browseMode === 'category' && this.browseType && this.browseId) {
                    return this.fetchBrowseProducts(1, false);
                }

                if ((this.query || '').trim()) {
                    return this.fetchProducts();
                }

                return Promise.resolve();
            },
            setQuery: function (value) {
                this.query = value || '';
            },
            setBarcodeQuery: function (value) {
                this.barcodeQuery = value || '';
            },
            onSearchFocus: function () {
                if ((this.query || '').trim()) {
                    global.PosV3.useUiStore().setSearchOpen(true);
                }
            },
            onBarcodeFocus: function () {
                this.barcodeQuery = '';
                global.PosV3.useUiStore().setSearchOpen(false);
            },
            clearResults: function () {
                this.products = [];
                this.hasMore = false;
                this.page = 1;
                this.browseMode = 'search';
                this.browseType = null;
                this.browseId = null;
                this.browseLabel = '';
                this.expandedProductId = null;
                global.PosV3.useUiStore().setSearchOpen(false);
            },
            hideProductResults: function () {
                global.PosV3.useUiStore().setSearchOpen(false);
            },
            closeProductResults: function () {
                this.hideProductResults();
            },
            maybeCloseAfterProductSelect: function () {
                if (global.PosV3.useSettingsStore().shouldHideProductListAfterSelect) {
                    this.clearResults();
                }
            },
            browseByCategory: function (type, id, label) {
                this.browseMode = 'category';
                this.browseType = type;
                this.browseId = id;
                this.browseLabel = label || '';
                this.query = '';
                this.page = 1;
                return this.fetchBrowseProducts(1, false);
            },
            loadMore: function () {
                if (!this.hasMore) {
                    return Promise.resolve();
                }

                return this.fetchBrowseProducts(this.page + 1, true);
            },
            fetchBrowseProducts: function (page, append) {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const productsUrl = configStore.routes.products;

                if (!productsUrl || !this.browseType || !this.browseId) {
                    return Promise.resolve();
                }

                uiStore.setLoading('search', true);
                uiStore.setSearchOpen(true);

                const params = {
                    page: page || 1,
                    per_page: this.perPage,
                };

                params[this.browseType] = this.browseId;

                return global.PosV3.api.get(productsUrl, params)
                    .then(function (response) {
                        const data = response.data && response.data.data ? response.data.data : {};
                        const items = Array.isArray(data.items) ? data.items : [];

                        this.products = append ? this.products.concat(items) : items;
                        this.hasMore = !!data.has_more;
                        this.page = page || 1;
                        this.expandedProductId = null;
                    }.bind(this))
                    .catch(function () {
                        if (!append) {
                            this.products = [];
                            this.hasMore = false;
                        }
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('search', false);
                    });
            },
            fetchProducts: function (page, append) {
                const query = (this.query || '').trim();
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const searchUrl = configStore.routes.search;
                const targetPage = page || 1;

                if (!query) {
                    this.products = [];
                    this.hasMore = false;
                    uiStore.setSearchOpen(false);
                    uiStore.setLoading('search', false);
                    return Promise.resolve();
                }

                this.browseMode = 'search';
                this.browseType = null;
                this.browseId = null;
                this.browseLabel = '';

                if (!searchUrl) {
                    return Promise.resolve();
                }

                uiStore.setLoading('search', true);
                uiStore.setSearchOpen(true);

                return global.PosV3.api.get(searchUrl, {
                    q: query,
                    page: targetPage,
                    per_page: this.perPage,
                })
                    .then(function (response) {
                        const payload = response.data && response.data.data ? response.data.data : {};
                        const items = Array.isArray(payload.items) ? payload.items : [];

                        this.products = append ? this.products.concat(items) : items;
                        this.hasMore = !!payload.has_more;
                        this.page = payload.page || targetPage;
                        this.expandedProductId = null;
                    }.bind(this))
                    .catch(function () {
                        if (!append) {
                            this.products = [];
                            this.hasMore = false;
                        }
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('search', false);
                    });
            },
            goToPage: function (page) {
                const target = parseInt(page, 10);

                if (!Number.isFinite(target) || target < 1) {
                    return Promise.resolve();
                }

                if (this.browseMode === 'category' && this.browseType && this.browseId) {
                    return this.fetchBrowseProducts(target, false);
                }

                if ((this.query || '').trim()) {
                    return this.fetchProducts(target, false);
                }

                return Promise.resolve();
            },
            selectProduct: function (product, variantPayload) {
                const payload = variantPayload || null;
                const builtItem = global.PosV3.cartItemBuilder.buildFromProduct(product, payload);

                if (!builtItem) {
                    return;
                }

                if (payload && payload.qty != null) {
                    const qty = Math.max(1, parseInt(payload.qty, 10) || 1);
                    builtItem.qty = qty;
                }

                global.PosV3.useCartStore().addBuiltItem(builtItem);
                this.maybeCloseAfterProductSelect();
            },
            lookupBarcode: function () {
                const code = (this.barcodeQuery || '').trim();
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const barcodeUrl = configStore.routes.productsByBarcode;

                if (!code || !barcodeUrl) {
                    return Promise.resolve();
                }

                uiStore.setLoading('barcode', true);

                return global.PosV3.api.post(barcodeUrl, { code: code })
                    .then(function (response) {
                        const product = response.data && response.data.data ? response.data.data : null;

                        if (!product) {
                            throw new Error('Product not found for barcode.');
                        }

                        global.PosV3.useCartStore().addFromProduct(product);
                        this.barcodeQuery = '';
                    }.bind(this))
                    .catch(function (error) {
                        const message = error.response && error.response.data && error.response.data.message
                            ? error.response.data.message
                            : 'Barcode lookup failed';

                        window.alert(message);
                    })
                    .finally(function () {
                        uiStore.setLoading('barcode', false);
                    });
            },
        },
    });
})(window);
