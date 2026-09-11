(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3GrandTotal = {
        name: 'PosV3GrandTotal',
        template: `
            <div class="pos-v3-totals-row pos-v3-totals-row--grand">
                <span>Grand Total</span>
                <strong>{{ formatMoney(grandTotal) }}</strong>
            </div>
        `,
        computed: {
            grandTotal: function () {
                return global.PosV3.useTotalsStore().grandTotal;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
        },
    };
})(window);
