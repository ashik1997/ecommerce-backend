(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CategorySidebar = {
        name: 'PosV3CategorySidebar',
        template: `
            <aside class="pos-v3-category-sidebar pos-v3-card" :class="{ 'is-loading': isLoading }">
                <div class="pos-v3-category-sidebar__header">
                    <strong>Categories</strong>
                    <div class="pos-v3-category-sidebar__header-actions">
                        <button
                            v-if="hasSelection"
                            type="button"
                            class="pos-v3-category-sidebar__clear"
                            @click="clearSelection">
                            Clear
                        </button>
                        <button
                            type="button"
                            class="pos-v3-category-sidebar__close"
                            title="Close"
                            @click="closeSidebar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <input
                    type="text"
                    class="pos-v3-input pos-v3-category-sidebar__search"
                    placeholder="Filter categories..."
                    :value="filter"
                    @input="setFilter($event.target.value)">
                <div class="pos-v3-category-sidebar__body">
                    <div
                        v-for="category in categories"
                        :key="'cat-' + category.id"
                        class="pos-v3-category-group">
                        <div class="pos-v3-category-row">
                            <button
                                type="button"
                                class="pos-v3-category-row__toggle"
                                v-if="category.subcategories && category.subcategories.length"
                                @click="toggleCategory(category.id)">
                                {{ isCategoryExpanded(category.id) ? '−' : '+' }}
                            </button>
                            <button
                                type="button"
                                class="pos-v3-category-row__label"
                                :class="{ 'is-active': isSelected('category_id', category.id) }"
                                @click="selectCategory('category_id', category.id, category.name)">
                                {{ category.name }}
                            </button>
                        </div>

                        <div v-if="isCategoryExpanded(category.id)" class="pos-v3-category-children">
                            <div
                                v-for="subcategory in category.subcategories"
                                :key="'sub-' + subcategory.id"
                                class="pos-v3-category-group pos-v3-category-group--nested">
                                <div class="pos-v3-category-row">
                                    <button
                                        type="button"
                                        class="pos-v3-category-row__toggle"
                                        v-if="subcategory.childcategories && subcategory.childcategories.length"
                                        @click="toggleSubcategory(category.id, subcategory.id)">
                                        {{ isSubcategoryExpanded(category.id, subcategory.id) ? '−' : '+' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="pos-v3-category-row__label"
                                        :class="{ 'is-active': isSelected('subcategory_id', subcategory.id) }"
                                        @click="selectCategory('subcategory_id', subcategory.id, subcategory.name)">
                                        {{ subcategory.name }}
                                    </button>
                                </div>

                                <div v-if="isSubcategoryExpanded(category.id, subcategory.id)" class="pos-v3-category-children">
                                    <button
                                        v-for="child in subcategory.childcategories"
                                        :key="'child-' + child.id"
                                        type="button"
                                        class="pos-v3-category-row__label pos-v3-category-row__label--child"
                                        :class="{ 'is-active': isSelected('childcategory_id', child.id) }"
                                        @click="selectCategory('childcategory_id', child.id, child.name)">
                                        {{ child.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!isLoading && !categories.length" class="pos-v3-category-sidebar__empty">
                        No categories found.
                    </div>
                </div>
            </aside>
        `,
        computed: {
            categoryStore: function () {
                return global.PosV3.useCategoryStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            categories: function () {
                return this.categoryStore.filteredCategories;
            },
            filter: function () {
                return this.categoryStore.filter;
            },
            hasSelection: function () {
                return !!this.categoryStore.selectedId;
            },
            isLoading: function () {
                return this.uiStore.loading.categories;
            },
        },
        methods: {
            setFilter: function (value) {
                this.categoryStore.setFilter(value);
            },
            toggleCategory: function (categoryId) {
                this.categoryStore.toggleCategory(categoryId);
            },
            toggleSubcategory: function (categoryId, subcategoryId) {
                this.categoryStore.toggleSubcategory(categoryId + '_' + subcategoryId);
            },
            isCategoryExpanded: function (categoryId) {
                return this.categoryStore.isCategoryExpanded(categoryId);
            },
            isSubcategoryExpanded: function (categoryId, subcategoryId) {
                return this.categoryStore.isSubcategoryExpanded(categoryId + '_' + subcategoryId);
            },
            selectCategory: function (type, id, label) {
                this.categoryStore.selectCategory(type, id, label);
                this.closeSidebar();
            },
            clearSelection: function () {
                this.categoryStore.clearSelection();
            },
            closeSidebar: function () {
                this.uiStore.setCategorySidebarOpen(false);
                this.$emit('close');
            },
            isSelected: function (type, id) {
                return this.categoryStore.isSelected(type, id);
            },
        },
    };
})(window);
