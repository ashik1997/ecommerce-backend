(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3RightPanel = {
        name: 'PosV3RightPanel',
        template: `
            <aside class="pos-v3-right">
                <div class="pos-v3-checkout-stack">
                    <pos-v3-totals-card></pos-v3-totals-card>
                    <pos-v3-payment-section></pos-v3-payment-section>
                    <pos-v3-hold-actions></pos-v3-hold-actions>
                    <pos-v3-order-actions></pos-v3-order-actions>
                </div>
            </aside>
        `,
    };
})(window);
