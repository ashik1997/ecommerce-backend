(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3RoundOffRow = {
        name: 'PosV3RoundOffRow',
        template: `
            <div class="pos-v3-totals-row">
                <span class="pos-v3-totals-label">Round Off</span>
                <div class="pos-v3-totals-row__right">
                    <input type="text" class="pos-v3-totals-input" :value="roundOff" @change="setRoundOff($event.target.value)">
                </div>
            </div>
        `,
        computed: {
            totalsStore: function () {
                return global.PosV3.useTotalsStore();
            },
            roundOff: function () {
                return this.totalsStore.roundOff;
            },
        },
        methods: {
            setRoundOff: function (value) {
                this.totalsStore.setRoundOff(value);
            },
        },
    };
})(window);
