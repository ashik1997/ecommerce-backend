(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CartRow = {
        name: 'PosV3CartRow',
        props: {
            item: {
                type: Object,
                required: true,
            },
            index: {
                type: Number,
                required: true,
            },
        },
        computed: {
            orderStore: function () {
                return global.PosV3.useOrderStore();
            },
            canEditDiscount: function () {
                return this.orderStore.selectedProductPriceType === 'product_price';
            },
            cartStore: function () {
                return global.PosV3.useCartStore();
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            updateField: function (field, event) {
                this.cartStore.updateItem(this.item.temp_id, field, event.target.value);
            },
            removeRow: function () {
                this.cartStore.removeItem(this.item.temp_id);
            },
        },
        template: `
            <tr>
                <td>
                    <div class="pos-v3-cart-item">
                        <img :src="item.image_url" alt="" class="pos-v3-cart-item__thumb">
                        <div class="pos-v3-cart-item__info">
                            <div class="pos-v3-cart-item__title" :title="item.title">{{ item.title }}</div>
                            <div v-if="item.variant_combination_key" class="pos-v3-cart-item__meta">
                                {{ item.variant_combination_key }} (avl: {{ item.max_qty }})
                            </div>
                            <div v-else class="pos-v3-cart-item__meta">Avl: {{ item.max_qty }}</div>
                            <div v-if="item.unit_code" class="pos-v3-cart-item__unit">{{ item.unit_code }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <input
                        type="text"
                        class="pos-v3-cart-input"
                        :value="item.qty"
                        @change="updateField('qty', $event)">
                </td>
                <td>
                    <input
                        type="text"
                        class="pos-v3-cart-input"
                        :value="item.unit_price"
                        @change="updateField('unit_price', $event)">
                </td>
                <td>
                    <input
                        type="text"
                        class="pos-v3-cart-input"
                        :value="item.discount.percent"
                        :disabled="!canEditDiscount"
                        @change="updateField('discount_percent', $event)">
                </td>
                <td>
                    <input
                        type="text"
                        class="pos-v3-cart-input"
                        :value="item.discount.fixed"
                        :disabled="!canEditDiscount"
                        @change="updateField('discount_fixed', $event)">
                </td>
                <td>{{ formatMoney(item.final_price) }}</td>
                <td>{{ formatMoney(item.line_total) }}</td>
                <td class="pos-v3-cart-row__action">
                    <button type="button" class="pos-v3-cart-remove" @click="removeRow">
                        <i class="feather-trash-2"></i>
                    </button>
                </td>
            </tr>
        `,
    };
})(window);
