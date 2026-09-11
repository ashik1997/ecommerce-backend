Vue.component('pos-product-item', {
    props: {
        p: {
            type: Object,
            required: true
        },
        formatMoney: {
            type: Function,
            default: () => '',
        },
        productItemSelect: {
            type: Function,
            default: () => {},
        },
        hide_product_search_result: {
            type: Function,
            default: () => {},
        },
        order_type: {
            type: String,
            default: 'pos',
        }
    },
    data: function () {
        return {
            show_variant_block: false,
            selectedByIndex: [], // selected value per variant (e.g. ['Yellow', 'SM', 'Cotton'])
        };
    },
    watch: {
        show_variant_block(val) {
            if (val && this.p.variant_values && this.p.variant_values.length) {
                this.selectedByIndex = this.p.variant_values.map(() => '');
            }
        },
        'p.variant_values': {
            handler(vals) {
                if (this.show_variant_block && vals && vals.length) {
                    this.selectedByIndex = vals.map(() => '');
                }
            },
            deep: true
        }
    },
    computed: {
        availableCombos() {
            const stocks = this.p.variant_stocks || {};
            const combos = this.p.product_variant_combinations || [];

            return combos
                .map((combo) => {
                    const key = this.variantKeyFromCombo(combo);
                    return {
                        key,
                        tokens: this.variantValuesFromCombo(combo).map((value) => this.normalizeToken(value)),
                        stock: Number(stocks[key] || 0),
                    };
                })
                .filter((combo) => combo.key && combo.stock > 0);
        },
        key_combination() {
            if (!this.p.variant_values || !this.p.variant_values.length) return '';
            const parts = this.p.variant_values.map((v, i) => {
                const val = (this.selectedByIndex[i] || '').trim();
                return val ? val : '';
                // return val ? val.toLowerCase().replace(/\s+/g, '-') : '';
            }).filter(Boolean);
            return parts.join('-');
        },
        allVariantSelected() {
            
            if (!this.p.variant_values || !this.p.variant_values.length) return false;
            return this.p.variant_values.every((v, i) => (this.selectedByIndex[i] || '').trim() !== '');
        },
        variantStock() {
            return this.p.variant_stocks && this.key_combination
                ? Number(this.p.variant_stocks[this.key_combination] || 0)
                : 0;
        },
        canAddVariant() {
            return this.allVariantSelected && this.key_combination && this.variantStock > 0;
        },
        // add new
        selectedVariant() {
            if (!this.p.has_variants || !this.key_combination) return null;

            return (this.p.product_variant_combinations || []).find(item => {
                return this.variantKeyFromCombo(item) === this.key_combination;
            }) || null;
        },

        displayPrice() {
            const main = this.displayMainPrice;
            const discountPrice = this.currentDiscountPrice();
            const discountFixed = discountPrice > 0 && discountPrice < main
                ? main - discountPrice
                : this.currentDiscountFixed();

            return this.roundPriceToFive(Math.max(0, main - Math.round(discountFixed)));
        },

        displayMainPrice() {
            if (this.p.has_variants && this.selectedVariant) {
                return Number(this.selectedVariant.price || 0);
            }

            return Number(this.p.main_price || this.p.unit_price || 0);
        },
    },
    methods: {
        formatMoney(v) {
            return (Number(v) || 0).toFixed(2);
        },
        roundPriceToFive(value) {
            return Math.max(0, Math.round((Number(value) || 0) / 5) * 5);
        },
        currentDiscountPrice() {
            if (this.p.has_variants && this.selectedVariant) {
                return Number(this.selectedVariant.discount_price || 0);
            }

            return Number(this.p.discount_price || 0);
        },
        currentDiscountFixed() {
            const source = this.p.has_variants && this.selectedVariant
                ? this.selectedVariant
                : this.p;
            const discount = source && source.discount ? source.discount : {};

            if (Number(discount.fixed || 0) > 0) {
                return Number(discount.fixed || 0);
            }

            const percent = Number(discount.percent || source.discount_parcent || 0);
            return this.displayMainPrice * (percent / 100);
        },
        normalizeToken(value) {
            return (value || '').trim().toLowerCase().replace(/\s+/g, '-');
        },
        variantAttributeKeys() {
            if (!this.p.variant_values || !this.p.variant_values.length) return [];

            return this.p.variant_values.reduce((keys, variantGroup) => {
                Object.keys(variantGroup || {}).forEach((key) => {
                    if (!keys.includes(key)) keys.push(key);
                });
                return keys;
            }, []);
        },
        variantValuesFromCombo(combo) {
            return this.variantAttributeKeys()
                .map((key) => combo && combo[key] != null ? String(combo[key]).trim() : '')
                .filter(Boolean);
        },
        variantKeyFromCombo(combo) {
            if (!combo) return '';
            if (combo.combination_key) return String(combo.combination_key).trim();

            return this.variantValuesFromCombo(combo).join('-');
        },
        getSelected(index) {
            return this.selectedByIndex[index] || '';
        },
        setSelected(index, value) {
            this.$set(this.selectedByIndex, index, value);
        },
        filteredVariantOptions(index, options) {
            if (!options || !options.length) return [];
            if (!this.availableCombos.length) return options;

            const baseTokens = this.selectedByIndex
                .map((val, i) => (i === index ? '' : this.normalizeToken(val)))
                .filter(Boolean);

            return options.filter((opt) => {
                const optToken = this.normalizeToken(opt);
                return this.availableCombos.some((combo) => {
                    if (!combo.tokens.includes(optToken)) return false;
                    return baseTokens.every((t) => combo.tokens.includes(t));
                });
            });
        },
        addWithVariant() {
            if (!this.selectedVariant) {
                alert('Please select valid variant');
                return;
            }
            console.log('Selected Variant:', this.selectedVariant);

            const variantMainPrice = Number(this.selectedVariant.price || 0);
            const variantDiscountPrice = Number(this.selectedVariant.discount_price || 0);
            const variantDiscountFixed = variantDiscountPrice > 0 && variantDiscountPrice < variantMainPrice
                ? variantMainPrice - variantDiscountPrice
                : 0;

            this.productItemSelect({
                ...this.p,

                // variant name product name er sathe add hobe
                // name: this.p.name + ' - ' + this.key_combination,

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
                variant_combination_key: this.key_combination,
                variant_name: this.key_combination,

                stock: this.variantStock,
                max_qty: this.variantStock,
            });

            this.hide_product_search_result();
        }
    },
    template: `
        <div class="pos-product-card pos-product-card-v2">
            <img :src="p.image_url" alt="" :title="p.name + ' (' + p.id + ')'" class="pos-product-thumb">
            <div class="pos_search_product_info">
                <div class="pos-product-name" :title="p.name">{{ p.name }}</div>
                <div class="pos-product-meta">
                    <div>
                        <div class="pos-product-price">
                            <span v-if="displayPrice && displayPrice < displayMainPrice">
                                <span style="font-size: 11px;">
                                    {{ formatMoney(displayPrice) }}
                                </span>

                                <del class="text-muted" style="font-size: 11px;">
                                    {{ formatMoney(displayMainPrice) }}
                                </del>
                            </span>

                            <span v-else>
                                {{ formatMoney(displayPrice) }}
                            </span>
                        </div>
                        <span class="text-muted" style="font-size: 11px;">Stock: {{ p.stock }}</span>
                    </div>

                    <button type="button" v-if="p.has_variants && p.stock > 0" class="pos-product-add" @click.stop="show_variant_block = !show_variant_block">
                        {{ show_variant_block ? 'hide' : 'select' }}
                    </button>
                    <button type="button" v-else-if="p.stock > 0 || order_type === 'quotation'" class="pos-product-add" @click.stop="()=>{productItemSelect(p); hide_product_search_result();}">
                        Add
                    </button>
                </div>
                <div v-if="show_variant_block" @click.stop>
                    <div v-for="(variant, index) in p.variant_values" :key="index">
                        <div v-for="(variant_item, key) in variant" :key="key" class="mb-1 pos_search_variant_item">
                            <div class="pos_search_variant_item_key">{{ key }}</div>
                            <select class="form-control pos_search_variant_item_select"
                                :value="getSelected(index)"
                                @input="setSelected(index, $event.target.value)">
                                <option value="">Select {{ key }}</option>
                                <option v-for="option in filteredVariantOptions(index, variant_item)" :key="option" :value="option">{{ option }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="pos_search_variant_item_stock">
                        <div v-if="key_combination">
                            available stock: {{ variantStock }}
                        </div>
                        <button type="button" class="pos-product-add" v-if="canAddVariant" @click="addWithVariant">
                            Add
                        </button>
                    </div>
                </div> 
            </div>
        `
});
