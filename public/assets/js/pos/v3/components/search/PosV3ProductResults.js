(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3ProductResults = {
        name: 'PosV3ProductResults',
        template: `
            <div class="pos-v3-product-results">
                <div class="pos-v3-product-results__header" v-if="resultsTitle">
                    <strong>{{ resultsTitle }}</strong>
                    <span class="pos-v3-product-results__count" v-if="products.length">
                        {{ products.length }} item{{ products.length === 1 ? '' : 's' }}
                    </span>
                </div>

                <div v-if="isLoading" class="pos-v3-product-results__loading">
                    <div class="pos-v3-product-results__spinner"></div>
                    <span>Loading products...</span>
                </div>

                <div class="pos-v3-product-results__list">
                    <pos-v3-product-item
                        v-for="product in products"
                        :key="product.id"
                        :product="product">
                    </pos-v3-product-item>

                    <div v-if="!isLoading && !products.length" class="pos-v3-product-results__empty">
                        No products found.
                    </div>
                </div>

                <div class="pos-v3-product-results__footer">
                    <div class="pos-v3-product-results__pagination">
                        <button
                            type="button"
                            class="pos-v3-product-results__page-btn"
                            :disabled="currentPage <= 1 || isLoading"
                            aria-label="Previous page"
                            @click="goToPage(currentPage - 1)">
                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        </button>

                        <button
                            v-for="page in visiblePages"
                            :key="page"
                            type="button"
                            class="pos-v3-product-results__page-btn"
                            :class="{ 'is-active': page === currentPage }"
                            :disabled="isLoading"
                            @click="goToPage(page)">
                            {{ page }}
                        </button>

                        <button
                            type="button"
                            class="pos-v3-product-results__page-btn"
                            :disabled="!hasMore || isLoading"
                            aria-label="Next page"
                            @click="goToPage(currentPage + 1)">
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="pos-v3-product-results__per-page">
                        <label for="pos-v3-per-page">Show:</label>
                        <select
                            id="pos-v3-per-page"
                            class="pos-v3-product-results__per-page-select"
                            :value="perPage"
                            @change="onPerPageChange">
                            <option v-for="option in perPageOptions" :key="option" :value="option">
                                {{ option }} per page
                            </option>
                        </select>
                    </div>

                    <button type="button" class="pos-v3-btn pos-v3-btn--danger pos-v3-product-results__close" @click="closeResults">
                        Close
                    </button>
                </div>
            </div>
        `,
        data: function () {
            return {
                perPageOptions: [10, 24, 48],
            };
        },
        computed: {
            products: function () {
                return this.searchStore.products;
            },
            isLoading: function () {
                return this.uiStore.loading.search;
            },
            hasMore: function () {
                return this.searchStore.hasMore;
            },
            currentPage: function () {
                return this.searchStore.page || 1;
            },
            perPage: function () {
                return this.searchStore.perPage;
            },
            visiblePages: function () {
                const current = this.currentPage;
                const pages = [];

                for (let page = Math.max(1, current - 2); page <= current; page += 1) {
                    pages.push(page);
                }

                if (this.hasMore) {
                    pages.push(current + 1);
                }

                return pages.length ? pages : [1];
            },
            resultsTitle: function () {
                if (this.searchStore.browseMode === 'category' && this.searchStore.browseLabel) {
                    return this.searchStore.browseLabel;
                }

                const query = (this.searchStore.query || '').trim();

                return query ? 'Search: ' + query : '';
            },
            searchStore: function () {
                return global.PosV3.useSearchStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
        },
        methods: {
            closeResults: function () {
                this.searchStore.closeProductResults();
            },
            onPerPageChange: function (event) {
                this.searchStore.setPerPage(event.target.value);
            },
            goToPage: function (page) {
                const target = parseInt(page, 10);

                if (!Number.isFinite(target) || target < 1 || target === this.currentPage) {
                    return;
                }

                if (target > this.currentPage && !this.hasMore) {
                    return;
                }

                this.searchStore.goToPage(target);
            },
        },
    };
})(window);
