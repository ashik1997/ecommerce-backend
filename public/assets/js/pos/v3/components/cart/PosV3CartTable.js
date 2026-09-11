(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3CartTable = {
        name: 'PosV3CartTable',
        template: `
            <div class="pos-v3-cart-table-wrap">
                <table class="pos-v3-cart-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Disc (%)</th>
                            <th>Disc (Tk)</th>
                            <th>Final</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <pos-v3-cart-row
                            v-for="(item, index) in items"
                            :key="item.temp_id"
                            :item="item"
                            :index="index">
                        </pos-v3-cart-row>
                        <tr v-if="!items.length">
                            <td colspan="8" class="pos-v3-cart-table__empty">No items in cart.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="items.length">
                        <tr>
                            <th colspan="6" class="text-right">Cart Subtotal</th>
                            <th colspan="2">{{ formatMoney(subtotal) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `,
        computed: {
            items: function () {
                return this.cartStore.items;
            },
            subtotal: function () {
                return this.cartStore.subtotal;
            },
            cartStore: function () {
                return global.PosV3.useCartStore();
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
        },
    };
})(window);
