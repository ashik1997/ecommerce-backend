(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    var COLOR_HEX_MAP = {
        beige: '#d4b896',
        black: '#1f2937',
        blue: '#3b82f6',
        brown: '#92400e',
        gray: '#9ca3af',
        grey: '#9ca3af',
        green: '#22c55e',
        orange: '#f97316',
        pink: '#f472b6',
        purple: '#a855f7',
        red: '#ef4444',
        white: '#f3f4f6',
        yellow: '#eab308',
    };

    global.PosV3.components.PosV3ProductItem = {
        name: 'PosV3ProductItem',
        props: {
            product: {
                type: Object,
                required: true,
            },
        },
        data: function () {
            return {
                qty: 1,
                selectedByIndex: [],
                addSuccess: false,
                isHovered: false,
                addSuccessTimer: null,
            };
        },
        computed: {
            attributeKeys: function () {
                return global.PosV3.variantHelpers.variantAttributeKeys(this.product.variant_values);
            },
            availableCombos: function () {
                const stocks = this.product.variant_stocks || {};
                const combos = this.product.product_variant_combinations || [];
                const helpers = global.PosV3.variantHelpers;
                const keys = this.attributeKeys;

                return combos
                    .map(function (combo) {
                        const key = helpers.variantKeyFromCombo(combo, keys);
                        return {
                            key: key,
                            combo: combo,
                            tokens: helpers.variantValuesFromCombo(combo, keys).map(function (value) {
                                return helpers.normalizeToken(value);
                            }),
                            stock: Number(stocks[key] || 0),
                        };
                    })
                    .filter(function (combo) {
                        return combo.key && combo.stock > 0;
                    });
            },
            keyCombination: function () {
                if (!this.product.variant_values || !this.product.variant_values.length) {
                    return '';
                }

                return this.product.variant_values.map(function (variant, index) {
                    return (this.selectedByIndex[index] || '').trim();
                }.bind(this)).filter(Boolean).join('-');
            },
            selectedVariant: function () {
                if (!this.product.has_variants || !this.keyCombination) {
                    return null;
                }

                const helpers = global.PosV3.variantHelpers;
                const keys = this.attributeKeys;

                return (this.product.product_variant_combinations || []).find(function (combo) {
                    return helpers.variantKeyFromCombo(combo, keys) === this.keyCombination;
                }.bind(this)) || null;
            },
            displayMainPrice: function () {
                if (this.product.has_variants && this.selectedVariant) {
                    return Number(this.selectedVariant.price || 0);
                }

                return Number(this.product.main_price || this.product.unit_price || 0);
            },
            displayPrice: function () {
                const main = this.displayMainPrice;
                const discountPrice = this.currentDiscountPrice();
                const discountFixed = discountPrice > 0 && discountPrice < main
                    ? main - discountPrice
                    : this.currentDiscountFixed();

                return Math.max(0, Math.round((main - Math.round(discountFixed)) / 5) * 5);
            },
            hasDiscount: function () {
                return this.displayPrice > 0 && this.displayPrice < this.displayMainPrice;
            },
            variantStock: function () {
                return this.product.variant_stocks && this.keyCombination
                    ? Number(this.product.variant_stocks[this.keyCombination] || 0)
                    : 0;
            },
            displayStock: function () {
                if (this.product.has_variants && this.allVariantSelected) {
                    return this.variantStock;
                }

                return Number(this.product.stock || 0);
            },
            effectiveStock: function () {
                const stock = this.displayStock;
                return Number.isFinite(stock) && stock > 0 ? stock : 0;
            },
            allVariantSelected: function () {
                if (!this.product.variant_values || !this.product.variant_values.length) {
                    return false;
                }

                return this.product.variant_values.every(function (variant, index) {
                    return (this.selectedByIndex[index] || '').trim() !== '';
                }.bind(this));
            },
            canAddVariant: function () {
                return this.allVariantSelected && this.keyCombination && this.variantStock > 0;
            },
            canAdd: function () {
                if (this.product.has_variants) {
                    return this.canAddVariant;
                }

                return this.effectiveStock > 0;
            },
            variantSummary: function () {
                if (!this.product.has_variants) {
                    return '';
                }

                const parts = this.selectedByIndex.filter(function (value) {
                    return (value || '').trim() !== '';
                });

                return parts.length ? parts.join(' / ') : '';
            },
            isExpanded: function () {
                return this.searchStore.expandedProductId === this.product.id;
            },
            searchStore: function () {
                return global.PosV3.useSearchStore();
            },
        },
        watch: {
            isExpanded: function (value) {
                if (value && this.product.has_variants) {
                    this.ensureSelection();
                }
            },
            keyCombination: function () {
                if (this.qty > this.effectiveStock && this.effectiveStock > 0) {
                    this.qty = this.effectiveStock;
                }
            },
            'product.id': function () {
                this.qty = 1;
                this.initDefaultSelection();
            },
        },
        created: function () {
            this.initDefaultSelection();
        },
        beforeUnmount: function () {
            if (this.addSuccessTimer) {
                global.clearTimeout(this.addSuccessTimer);
            }
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            formatAttributeLabel: function (key) {
                if (!key) {
                    return '';
                }

                return String(key).replace(/_/g, ' ').replace(/\b\w/g, function (char) {
                    return char.toUpperCase();
                });
            },
            isColorKey: function (key) {
                const normalized = String(key || '').trim().toLowerCase();
                return normalized === 'color' || normalized === 'colour';
            },
            colorHex: function (name) {
                const token = global.PosV3.variantHelpers.normalizeToken(name);
                return COLOR_HEX_MAP[token] || '#cbd5e1';
            },
            currentDiscountPrice: function () {
                if (this.product.has_variants && this.selectedVariant) {
                    return Number(this.selectedVariant.discount_price || 0);
                }

                return Number(this.product.discount_price || 0);
            },
            currentDiscountFixed: function () {
                const source = this.product.has_variants && this.selectedVariant
                    ? this.selectedVariant
                    : this.product;
                const discount = source && source.discount ? source.discount : {};

                if (Number(discount.fixed || 0) > 0) {
                    return Number(discount.fixed || 0);
                }

                const percent = Number(discount.percent || source.discount_parcent || 0);
                return this.displayMainPrice * (percent / 100);
            },
            getSelected: function (index) {
                return this.selectedByIndex[index] || '';
            },
            setSelected: function (index, value) {
                this.selectedByIndex[index] = value;
            },
            isOptionSelected: function (index, option) {
                return this.getSelected(index) === option;
            },
            isOptionAvailable: function (index, options, option) {
                if (!options || !options.length) {
                    return false;
                }

                if (!this.availableCombos.length) {
                    return true;
                }

                const helpers = global.PosV3.variantHelpers;
                const optToken = helpers.normalizeToken(option);
                const baseTokens = this.selectedByIndex
                    .map(function (val, i) {
                        return i === index ? '' : helpers.normalizeToken(val);
                    })
                    .filter(Boolean);

                return this.availableCombos.some(function (combo) {
                    if (!combo.tokens.includes(optToken)) {
                        return false;
                    }

                    return baseTokens.every(function (token) {
                        return combo.tokens.includes(token);
                    });
                });
            },
            initDefaultSelection: function () {
                if (!this.product.has_variants || !this.product.variant_values || !this.product.variant_values.length) {
                    this.selectedByIndex = [];
                    return;
                }

                const helpers = global.PosV3.variantHelpers;
                const keys = this.attributeKeys;
                const stocks = this.product.variant_stocks || {};
                const combos = (this.product.product_variant_combinations || []).filter(function (combo) {
                    const key = helpers.variantKeyFromCombo(combo, keys);
                    return Number(stocks[key] || 0) > 0;
                });

                if (!combos.length) {
                    this.selectedByIndex = this.product.variant_values.map(function () {
                        return '';
                    });
                    return;
                }

                const first = combos[0];
                this.selectedByIndex = keys.map(function (key) {
                    return first[key] != null ? String(first[key]).trim() : '';
                });
            },
            ensureSelection: function () {
                const hasSelection = this.selectedByIndex.some(function (value) {
                    return (value || '').trim() !== '';
                });

                if (!hasSelection) {
                    this.initDefaultSelection();
                }
            },
            toggleExpand: function () {
                if (!this.product.has_variants) {
                    return;
                }

                if (this.isExpanded) {
                    this.searchStore.setExpandedProduct(null);
                    return;
                }

                this.searchStore.setExpandedProduct(this.product.id);
            },
            decrementQty: function () {
                if (this.qty > 1) {
                    this.qty -= 1;
                }
            },
            incrementQty: function () {
                if (this.qty < this.effectiveStock) {
                    this.qty += 1;
                }
            },
            onRowEnter: function () {
                this.isHovered = true;
            },
            onRowLeave: function () {
                this.isHovered = false;
            },
            showAddSuccess: function () {
                this.addSuccess = true;

                if (global.PosV3.toast && global.PosV3.toast.productAdded) {
                    global.PosV3.toast.productAdded(this.product.name);
                }

                if (this.addSuccessTimer) {
                    global.clearTimeout(this.addSuccessTimer);
                }

                this.addSuccessTimer = global.setTimeout(function () {
                    this.addSuccess = false;
                    this.addSuccessTimer = null;
                }.bind(this), 1600);
            },
            handleAdd: function () {
                if (!this.canAdd) {
                    return;
                }

                if (this.product.has_variants) {
                    this.addWithVariant();
                    return;
                }

                this.addSimpleProduct();
            },
            addSimpleProduct: function () {
                this.searchStore.selectProduct(this.product, { qty: this.qty });
                this.qty = 1;
                this.showAddSuccess();
            },
            addWithVariant: function () {
                if (!this.selectedVariant) {
                    return;
                }

                const variantMainPrice = Number(this.selectedVariant.price || 0);
                const variantDiscountPrice = Number(this.selectedVariant.discount_price || 0);
                const variantDiscountFixed = variantDiscountPrice > 0 && variantDiscountPrice < variantMainPrice
                    ? variantMainPrice - variantDiscountPrice
                    : 0;

                this.searchStore.selectProduct(this.product, Object.assign({}, this.product, {
                    unit_price: variantMainPrice,
                    main_price: variantMainPrice,
                    discount_price: variantDiscountPrice,
                    discount: {
                        type: 'fixed',
                        percent: 0,
                        fixed: variantDiscountFixed,
                        value: variantDiscountFixed,
                        amount: 0,
                    },
                    product_variant_id: this.selectedVariant.id,
                    variant_combination_key: this.keyCombination,
                    variant_name: this.keyCombination,
                    stock: this.variantStock,
                    max_qty: this.variantStock,
                    qty: this.qty,
                }));

                this.qty = 1;
                this.showAddSuccess();
            },
        },
        template: `
            <article
                class="pos-v3-product-item"
                :class="{
                    'is-expanded': isExpanded,
                    'is-out-of-stock': effectiveStock <= 0,
                    'is-hovered': isHovered
                }"
                @mouseenter="onRowEnter"
                @mouseleave="onRowLeave">
                <div class="pos-v3-product-item__main">
                    <img
                        :src="product.image_url"
                        :alt="product.name"
                        class="pos-v3-product-item__thumb">

                    <div class="pos-v3-product-item__info">
                        <div class="pos-v3-product-item__name" :title="product.name">{{ product.name }}</div>
                        <div class="pos-v3-product-item__pricing">
                            <span class="pos-v3-product-item__price">{{ formatMoney(displayPrice) }}</span>
                            <del v-if="hasDiscount" class="pos-v3-product-item__price-old">{{ formatMoney(displayMainPrice) }}</del>
                            <span class="pos-v3-product-item__stock">Stock: {{ displayStock }}</span>
                        </div>
                    </div>

                    <div class="pos-v3-product-item__actions">
                        <div
                            v-if="product.has_variants && variantSummary"
                            class="pos-v3-product-item__variant-badge"
                            :title="variantSummary">
                            {{ variantSummary }}
                        </div>

                        <div class="pos-v3-product-item__qty" @click.stop>
                            <button
                                type="button"
                                class="pos-v3-product-item__qty-btn"
                                :disabled="qty <= 1"
                                aria-label="Decrease quantity"
                                @click="decrementQty">−</button>
                            <span class="pos-v3-product-item__qty-value">{{ qty }}</span>
                            <button
                                type="button"
                                class="pos-v3-product-item__qty-btn"
                                :disabled="qty >= effectiveStock || effectiveStock <= 0"
                                aria-label="Increase quantity"
                                @click="incrementQty">+</button>
                        </div>

                        <button
                            type="button"
                            class="pos-v3-product-item__add"
                            :class="{ 'is-success': addSuccess }"
                            :disabled="!canAdd && !addSuccess"
                            @click.stop="handleAdd">
                            <i
                                class="fas pos-v3-product-item__add-icon"
                                :class="addSuccess ? 'fa-check' : 'fa-cart-plus'"
                                aria-hidden="true"></i>
                            <span>{{ addSuccess ? 'Added' : 'Add' }}</span>
                        </button>

                        <button
                            v-if="product.has_variants"
                            type="button"
                            class="pos-v3-product-item__expand"
                            :aria-expanded="isExpanded ? 'true' : 'false'"
                            :title="isExpanded ? 'Collapse variants' : 'Expand variants'"
                            @click.stop="toggleExpand">
                            <i class="fas" :class="isExpanded ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div
                    v-if="isExpanded && product.has_variants"
                    class="pos-v3-product-item__expanded"
                    @click.stop>
                    <div class="pos-v3-product-item__expanded-inner">
                        <div
                            v-for="(variant, index) in product.variant_values"
                            :key="index"
                            class="pos-v3-product-item__variant-group">
                            <template v-for="(options, key) in variant" :key="key">
                                <div class="pos-v3-product-item__variant-label">{{ formatAttributeLabel(key) }}:</div>
                                <div class="pos-v3-product-item__variant-options">
                                    <button
                                        v-for="option in options"
                                        :key="option"
                                        type="button"
                                        class="pos-v3-variant-chip"
                                        :class="{
                                            'is-selected': isOptionSelected(index, option),
                                            'is-disabled': !isOptionAvailable(index, options, option)
                                        }"
                                        :disabled="!isOptionAvailable(index, options, option)"
                                        @click="setSelected(index, option)">
                                        <span
                                            v-if="isColorKey(key)"
                                            class="pos-v3-variant-chip__swatch"
                                            :style="{ backgroundColor: colorHex(option) }"></span>
                                        <span class="pos-v3-variant-chip__label">{{ option }}</span>
                                        <i
                                            v-if="isOptionSelected(index, option)"
                                            class="fas fa-check pos-v3-variant-chip__icon"
                                            aria-hidden="true"></i>
                                        <i
                                            v-else-if="!isOptionAvailable(index, options, option)"
                                            class="fas fa-times pos-v3-variant-chip__icon pos-v3-variant-chip__icon--muted"
                                            aria-hidden="true"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div v-if="variantSummary" class="pos-v3-product-item__selected-summary">
                            Selected: {{ variantSummary }}
                            <span
                                v-if="allVariantSelected"
                                class="pos-v3-product-item__selected-stock"
                                :class="{ 'is-no-stock': variantStock <= 0 }">
                                · {{ variantStock > 0 ? ('Stock: ' + variantStock) : 'No stock' }}
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        `,
    };
})(window);
