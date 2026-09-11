(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3ExtraChargeRow = {
        name: 'PosV3ExtraChargeRow',
        template: `
            <div class="pos-v3-totals-row">
                <div class="pos-v3-totals-row__left">
                    <span class="pos-v3-totals-label">Extra Charge</span>
                    <pos-v3-extra-charge-manage></pos-v3-extra-charge-manage>
                </div>
                <div class="pos-v3-totals-row__right">
                    <input
                        type="text"
                        class="pos-v3-totals-input"
                        :value="extraCharge"
                        :readonly="useExtraChargeLines"
                        :title="useExtraChargeLines ? 'Total from charge lines — use manage button to edit' : ''"
                        @change="setExtraCharge($event.target.value)">
                </div>
            </div>
        `,
        computed: {
            totalsStore: function () {
                return global.PosV3.useTotalsStore();
            },
            extraCharge: function () {
                return this.totalsStore.extraCharge;
            },
            useExtraChargeLines: function () {
                return this.totalsStore.useExtraChargeLines || this.totalsStore.extraChargeLines.length > 0;
            },
        },
        methods: {
            setExtraCharge: function (value) {
                this.totalsStore.setExtraCharge(value);
            },
        },
    };
})(window);
