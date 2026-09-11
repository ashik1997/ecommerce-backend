(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useCategoryStore = Pinia.defineStore('posV3Category', {
        state: function () {
            return {
                categories: [],
                filter: '',
                expandedCategories: {},
                expandedSubcategories: {},
                selectedType: null,
                selectedId: null,
                selectedLabel: '',
            };
        },
        getters: {
            filteredCategories: function (state) {
                const term = (state.filter || '').trim().toLowerCase();

                if (!term) {
                    return state.categories;
                }

                return state.categories.map(function (category) {
                    const categoryMatch = String(category.name || '').toLowerCase().includes(term);
                    const subcategories = (category.subcategories || []).map(function (subcategory) {
                        const subMatch = String(subcategory.name || '').toLowerCase().includes(term);
                        const childcategories = (subcategory.childcategories || []).filter(function (child) {
                            return String(child.name || '').toLowerCase().includes(term);
                        });

                        if (subMatch || childcategories.length) {
                            return Object.assign({}, subcategory, { childcategories: childcategories });
                        }

                        return null;
                    }).filter(Boolean);

                    if (categoryMatch || subcategories.length) {
                        return Object.assign({}, category, { subcategories: subcategories });
                    }

                    return null;
                }).filter(Boolean);
            },
        },
        actions: {
            loadCategories: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.nestedCategories;

                if (!route) {
                    return Promise.resolve();
                }

                uiStore.setLoading('categories', true);

                return global.PosV3.api.get(route)
                    .then(function (response) {
                        this.categories = response.data && response.data.data
                            ? response.data.data
                            : [];
                    }.bind(this))
                    .catch(function () {
                        this.categories = [];
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('categories', false);
                    });
            },
            setFilter: function (value) {
                this.filter = value || '';
            },
            toggleCategory: function (categoryId) {
                this.expandedCategories[categoryId] = !this.expandedCategories[categoryId];
            },
            toggleSubcategory: function (key) {
                this.expandedSubcategories[key] = !this.expandedSubcategories[key];
            },
            isCategoryExpanded: function (categoryId) {
                return !!this.expandedCategories[categoryId];
            },
            isSubcategoryExpanded: function (key) {
                return !!this.expandedSubcategories[key];
            },
            selectCategory: function (type, id, label) {
                this.selectedType = type;
                this.selectedId = id;
                this.selectedLabel = label || '';
                global.PosV3.useSearchStore().browseByCategory(type, id, label);
            },
            clearSelection: function () {
                this.selectedType = null;
                this.selectedId = null;
                this.selectedLabel = '';
                global.PosV3.useSearchStore().clearResults();
            },
            isSelected: function (type, id) {
                return this.selectedType === type && String(this.selectedId) === String(id);
            },
        },
    });
})(window);
