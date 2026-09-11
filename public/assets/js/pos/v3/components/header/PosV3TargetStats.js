(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3TargetStats = {
        name: 'PosV3TargetStats',
        template: `
            <div class="pos-v3-target-stats" v-if="stats">
                <div class="pos-v3-target-stats__card pos-v3-target-stats__card--target">
                    <small>Target</small>
                    <strong>{{ formatMoney(stats.total_targets) }}</strong>
                </div>
                <div class="pos-v3-target-stats__card pos-v3-target-stats__card--sales">
                    <small>Sales</small>
                    <strong>{{ formatMoney(stats.sales) }}</strong>
                </div>
                <div class="pos-v3-target-stats__card pos-v3-target-stats__card--remains">
                    <small>Remains</small>
                    <strong>{{ formatMoney(stats.remains) }}</strong>
                </div>
                <div class="pos-v3-target-stats__card pos-v3-target-stats__card--achieve">
                    <small>Achieve %</small>
                    <strong>{{ stats.achieve_percent }}%</strong>
                </div>
            </div>
        `,
        computed: {
            stats: function () {
                return global.PosV3.useTargetStatsStore().stats;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
        },
    };
})(window);
